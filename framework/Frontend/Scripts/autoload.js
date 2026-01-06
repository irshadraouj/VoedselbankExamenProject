import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

// Determine the directory of the current script
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Define a function to locate the project root
function findProjectRoot(startDir) {
  let currentDir = startDir;

  while (currentDir !== path.parse(currentDir).root) {
    if (fs.existsSync(path.join(currentDir, 'package.json'))) {
      return currentDir;
    }
    currentDir = path.dirname(currentDir);
  }

  throw new Error('Project root not found');
}

// Define the directories
const projectRoot = findProjectRoot(__dirname);
const componentsDir = path.resolve(projectRoot, 'resources/components');
const mainCssPath = path.resolve(projectRoot, 'resources/base.css');
const mainScssPath = path.resolve(projectRoot, 'resources/styles.scss');
const mainJsPath = path.resolve(projectRoot, 'resources/main.js');

// Check if the components directory exists
if (!fs.existsSync(componentsDir)) {
  console.error(`\x1b[31mDirectory does not exist: ${componentsDir}\x1b[0m`); // Red text
  process.exit(1);
}

// Function to recursively get files
function getFiles(dir, fileTypes) {
  let results = [];
  const list = fs.readdirSync(dir);

  list.forEach((file) => {
    const filePath = path.join(dir, file);
    const stat = fs.statSync(filePath);

    if (stat.isDirectory()) {
      results = results.concat(getFiles(filePath, fileTypes));
    } else if (fileTypes.some(type => file.endsWith(type))) {
      results.push(path.relative(path.dirname(mainScssPath), filePath).replace(/\\/g, '/'));
    }
  });

  return results;
}

// Function to update a main file with imports placed between specific comments
function updateMainFileWithComments(existingContent, newImports, startComment, endComment) {
  const contentParts = existingContent.split(startComment);
  if (contentParts.length !== 2) {
    throw new Error(`Could not find the start comment (${startComment}) in the file`);
  }

  const [beforeImports, afterStart] = contentParts;
  const afterImports = afterStart.split(endComment)[1];

  if (typeof afterImports === 'undefined') {
    throw new Error(`Could not find the end comment (${endComment}) in the file`);
  }

  const newContent = `${beforeImports}${startComment}\n${newImports}\n${endComment}${afterImports}`;

  return newContent.trim(); // Ensure no extra whitespace
}

// Group files by component, ensuring SCSS and JS are both included
function groupFilesByComponent(scssFiles, jsFiles, cssFiles) {
  const groupedFiles = {};

  scssFiles.forEach(file => {
    const parts = file.split('/');
    const componentName = parts[1]; // Component name is the second directory level

    if (!groupedFiles[componentName]) {
      groupedFiles[componentName] = [];
    }
    groupedFiles[componentName].push({ file, type: 'scss' });
  });

  jsFiles.forEach(file => {
    const parts = file.split('/');
    const componentName = parts[1]; // Component name is the second directory level

    if (!groupedFiles[componentName]) {
      groupedFiles[componentName] = [];
    }
    groupedFiles[componentName].push({ file, type: 'js' });
  });

  cssFiles.forEach(file => {
    const parts = file.split('/');
    const componentName = parts[1]; // Component name is the second directory level

    if (!groupedFiles[componentName]) {
      groupedFiles[componentName] = [];
    }
    groupedFiles[componentName].push({ file, type: 'css' });
  });

  return groupedFiles;
}

// Get all SCSS and JavaScript files
const scssFiles = getFiles(componentsDir, ['.scss']);
const jsFiles = getFiles(componentsDir, ['.js']);
const cssFiles = getFiles(componentsDir, ['.css']);

// Combine SCSS and JS files by component
const allFilesByComponent = groupFilesByComponent(scssFiles, jsFiles, cssFiles);

// Collect log messages
console.log('\x1b[36m--- Components and Files ---\x1b[0m'); // Cyan text

Object.keys(allFilesByComponent).forEach(component => {
  console.log(`\x1b[34mComponent: ${component.charAt(0).toUpperCase() + component.slice(1)}\x1b[0m`); // Blue text
  allFilesByComponent[component].forEach(({ file, type }) => {
    const color = (type === 'scss' || type === 'css') ? '\x1b[32m' : '\x1b[33m'; // Green for SCSS, Yellow for JS
    console.log(`  ${color}- ${file}\x1b[0m`);
  });
});

// Ensure we don't duplicate imports
const scssImports = scssFiles.map(file => `@use '@${file}';`).join('\n').trim();
const cssImports = cssFiles.map(file => `@import '@${file}';`).join('\n').trim();
const jsImports = jsFiles.map(file => `import '@${file}';`).join('\n').trim();

// Read existing content of SCSS and JS files
const existingScssContent = fs.existsSync(mainScssPath) ? fs.readFileSync(mainScssPath, 'utf8') : '';
const existingCssContent = fs.existsSync(mainCssPath) ? fs.readFileSync(mainCssPath, 'utf8') : '';
const existingJsContent = fs.existsSync(mainJsPath) ? fs.readFileSync(mainJsPath, 'utf8') : '';


// Update and write the imports to the SCSS and JS files
const updatedScssContent = updateMainFileWithComments(
  existingScssContent,
  scssImports,
  '/* ===== Auto-Generated Imports ===== */',
  '/* ===== End Auto-Generated Imports ===== */'
);
const updatedCssContent = updateMainFileWithComments(
  existingCssContent,
  cssImports,
  '/* ===== Auto-Generated Imports ===== */',
  '/* ===== End Auto-Generated Imports ===== */'
);
const updatedJsContent = updateMainFileWithComments(
  existingJsContent,
  jsImports,
  '// ===== Auto-Generated Imports =====',
  '// ===== End Auto-Generated Imports ====='
);

fs.writeFileSync(mainScssPath, updatedScssContent, 'utf8');
fs.writeFileSync(mainCssPath, updatedCssContent, 'utf8');
fs.writeFileSync(mainJsPath, updatedJsContent, 'utf8');

// Log the relative paths of the updated files
console.log(`\x1b[36mUpdated SCSS file: ${path.relative(projectRoot, mainScssPath)}\x1b[0m`); // Cyan text
console.log(`\x1b[36mUpdated CSS file: ${path.relative(projectRoot, mainCssPath)}\x1b[0m`); // Cyan text
console.log(`\x1b[36mUpdated JS file: ${path.relative(projectRoot, mainJsPath)}\x1b[0m`); // Cyan text
