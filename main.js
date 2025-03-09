const fs = require('fs');

// Read the file
const filePath = 'templates/layout.html.twig';
let content = fs.readFileSync(filePath, 'utf8');

// Replace img src patterns
content = content.replace(/src="img\/([\w-]+\.jpg)"/g, 'src="{{ asset(\'backOffice/img/$1\') }}"');

// Replace href patterns for lightbox
content = content.replace(/href="img\/([\w-]+\.jpg)"/g, 'href="{{ asset(\'backOffice/img/$1\') }}"');

// Write the modified content back to file
fs.writeFileSync(filePath, content);

console.log('File updated successfully!');