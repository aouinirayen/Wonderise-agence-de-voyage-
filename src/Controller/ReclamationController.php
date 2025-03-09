<?php

namespace App\Controller;

use App\Entity\Reclamation;
use App\Form\ReclamationType;
use App\Repository\ReclamationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\Date;
use Knp\Snappy\Pdf;
use Twig\Environment;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Enum\StatusEnum;
#[Route('/reclamation')]

final class ReclamationController extends AbstractController{

    #[Route('/frontoff',name: 'app_reclamation_index1', methods: ['GET'])]
    public function index1(ReclamationRepository $reclamationRepository): Response
    {
        return $this->render('front_office/reclamation/index1.html.twig', [
            'reclamations' => $reclamationRepository->findAll(),
        ]);
    }
   

    #[Route(name: 'app_reclamation_index', methods: ['GET'])]
    public function index(
        ReclamationRepository $reclamationRepository,
        Request $request,
        PaginatorInterface $paginator,
        Registry $workflowRegistry // 🔥 Ajout du service de workflow
    ): Response {
        // Récupérer les filtres et le tri depuis la requête
        $keyword = $request->query->get('keyword');
        $status = $request->query->get('status');
        $order = $request->query->get('order', 'DESC'); 
    
        // Récupérer la requête Doctrine filtrée
        $query = $reclamationRepository->searchReclamations($keyword, $status, $order);
    
        // Paginer les résultats avec KnpPaginator
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );
    
        // Parcourir chaque réclamation pour récupérer les transitions disponibles
        foreach ($pagination as $reclamation) {
            $workflow = $workflowRegistry->get($reclamation, 'reclamation');
            $reclamation->availableTransitions = array_map(
                fn($t) => $t->getName(),
                $workflow->getEnabledTransitions($reclamation)
            );
        }
    
        return $this->render('back_office/reclamation/index.html.twig', [
            'pagination' => $pagination,
            'keyword' => $keyword ?? '',
            'status' => $status ?? '',
            'order' => $order ?? 'DESC',
        ]);
    }
    
    

    #[Route('/new', name: 'app_reclamation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($reclamation);
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_office/reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }
    #[Route('/newfront', name: 'app_reclamation_newfront', methods: ['GET', 'POST'])]
    

    public function newfront(Request $request, EntityManagerInterface $entityManager): Response
    {
        $reclamation = new Reclamation();
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $reclamation->setStatus(StatusEnum::EnCours);

            $entityManager->persist($reclamation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre réclamation a été envoyée avec succès !');
            return $this->redirectToRoute('app_reclamation_newfront', [], Response::HTTP_SEE_OTHER);
        }
    
        return $this->render('front_office/reclamation/new.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }
    
    #[Route('/back/{id}', name: 'app_reclamation_show_back', methods: ['GET'])]
       public function show(Reclamation $reclamation): Response
    {
        return $this->render('back_office/reclamation/show.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_reclamation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReclamationType::class, $reclamation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back_office/reclamation/edit.html.twig', [
            'reclamation' => $reclamation,
            'form' => $form,
        ]);
    }
    #[Route('/front/{id}', name: 'app_reclamation_show_front', methods: ['GET'])]
    public function showFront(Reclamation $reclamation): Response
    {
        return $this->render('front_office/reclamation/showdetails.html.twig', [
            'reclamation' => $reclamation,
        ]);
    }
    
    #[Route('/delete/{id}', name: 'app_reclamation_delete', methods: ['GET'])]
    public function delete(Request $request, Reclamation $reclamation, EntityManagerInterface $entityManager): Response
    {
       // if ($this->isCsrfTokenValid('delete'.$reclamation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($reclamation);
            $entityManager->flush();
      //  }

        return $this->redirectToRoute('app_reclamation_index', [], Response::HTTP_SEE_OTHER);
    }
   



#[Route('/chatbot', name: 'app_reclamation_chatbot', methods: ['GET'])]
public function chatbot(): Response
{
    return $this->render('front_office/reclamation/chatbot.html.twig');
}
#[Route('/{id}/transition/{transition}', name: 'app_reclamation_transition', methods: ['GET'])]
public function transition(
    Reclamation $reclamation,
    string $transition,
    Registry $workflowRegistry,
    EntityManagerInterface $entityManager
): Response {
    // 🔥 Get the workflow for the Reclamation entity
    $workflow = $workflowRegistry->get($reclamation, 'reclamation');

    // 🔥 Check if the transition is possible
    if ($workflow->can($reclamation, $transition)) {
        $workflow->apply($reclamation, $transition); // ✅ Apply the transition
        $reclamation->setStatus($transition); // ✅ Convert and store status as string
        $entityManager->persist($reclamation);
        $entityManager->flush();

        $this->addFlash('success', "Statut changé à '{$reclamation->getStatus()}'");
    } else {
        $this->addFlash('error', "Impossible d'effectuer cette transition !");
    }

    return $this->redirectToRoute('app_reclamation_index');
}



}