import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import readline from "readline";
import { exec } from "child_process";
import { promisify } from "util";
import {
  logWithHeading,
  displayCelebration,
  printErrorWithJoke,
  displayFatal,
} from "./logger.js";
import { fetchDadJoke } from "./dadJoke.js";

// Convert exec to return a promise
const execPromise = promisify(exec);

// Determine the directory of the current script
const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Define a function to locate the project root
function findProjectRoot(startDir) {
  let currentDir = startDir;

  while (currentDir !== path.parse(currentDir).root) {
    if (fs.existsSync(path.join(currentDir, "package.json"))) {
      return currentDir;
    }
    currentDir = path.dirname(currentDir);
  }

  throw new Error("Project root not found");
}

// Define the directories
const projectRoot = findProjectRoot(__dirname);
const componentsDir = path.resolve(projectRoot, "resources/components");

// Get the component name from the command line arguments
const componentName = process.argv[2];

if (!componentName) {
  displayFatal("\x1b[31mPlease provide a component name.\x1b[0m"); // Red text
  process.exit(1);
}

// Define paths for the new component
const componentDir = path.resolve(componentsDir, componentName);

// Check if the component directory already exists
if (fs.existsSync(componentDir)) {
  displayFatal(
    `\x1b[31mComponent directory already exists:\n${componentDir}\x1b[0m`
  ); // Red text

  // Fetch dad joke
  const joke = await fetchDadJoke();

  // Array of pun options for the error message
  const errorPuns = [
    "Looks like this component directory is already on the map!\nTime for a Dad Joke to navigate away from this error:",
    "Well, well, well, this directory already exists!\nLet's crack a Dad Joke to ease the path forward:",
    "Seems like this component directory is already booked!\nHere’s a Dad Joke to lighten the load:",
    "The directory’s already taken!\nHere’s a Dad Joke to make sure your mood isn't occupied:",
    "Oops! This component directory already exists.\nLet’s bring in a Dad Joke to break the ice:",
    "The component directory’s already in place!\nHere’s a Dad Joke to make sure you’re not in a bind:",
    "Looks like this directory's got a head start!\nLet's distract ourselves with a Dad Joke while we regroup:",
    "It appears the component directory’s already set up shop!\nHere’s a Dad Joke to lift your spirits:",
    "This directory is already hosting a component!\nTime for a Dad Joke to ease the congestion:",
    "The directory is already occupied!\nHere’s a Dad Joke to help you lighten up:",
  ];

  // Define the error message
  const errorMessage =
    errorPuns[Math.floor(Math.random() * errorPuns.length)] + "\n";

  // Print the error message and joke
  printErrorWithJoke(errorMessage, joke);
  process.exit(1);
}

logWithHeading(`Creating component: ${componentName}`);

// Create the new component directory
fs.mkdirSync(componentDir, { recursive: true });

// Prompt user to create a CSS or SCSS file
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout,
});

rl.question(
  "Choose a style file to create:\n1. CSS\n2. SCSS\nEnter 1 or 2: ",
  async (fileTypeChoice) => {
    let fileType;
    if (fileTypeChoice === "1") {
      fileType = "css";
    } else if (fileTypeChoice === "2") {
      fileType = "scss";
    } else {
      console.log(
        "\x1b[31mInvalid input! Please choose 1 for CSS or 2 for SCSS.\x1b[0m"
      );
      rl.close();
      process.exit(1);
    }

    const styleFilePath = path.resolve(
      componentDir,
      `${componentName}.${fileType}`
    );

    // Create file with basic content based on the selected file type
    const styleContent =
      fileType === "scss"
        ? `// Styles for ${componentName}\n\n.${componentName.toLowerCase()} {\n  // Add styles for ${componentName} here\n}\n`
        : `/* Styles for ${componentName} */\n@layer components {\n .${componentName.toLowerCase()} {\n   /* Add styles for ${componentName} here */\n  }\n}\n`;

    fs.writeFileSync(styleFilePath, styleContent, "utf8");
    console.log(
      `\n\x1b[32m${fileType.toUpperCase()} file created: ${path.relative(
        projectRoot,
        styleFilePath
      )}\x1b[0m`
    ); // Green text

    // Prompt user to create a JavaScript file
    rl.question(
      "Choose a JavaScript setup:\n1. Vanilla JS\n2. AlpineJS\n3. No JavaScript\nEnter 1, 2, or 3: ",
      async (jsChoice) => {
        if (jsChoice === "1") {
          // Create Vanilla JS file with basic content
          const jsFilePath = path.resolve(componentDir, `${componentName}.js`);
          const jsContent = `// JavaScript for ${componentName}\n\n(()=>{\n\n// Make magic!\n\n})();`;
          fs.writeFileSync(jsFilePath, jsContent, "utf8");
          console.log(
            `\x1b[32mVanilla JS file created: ${path.relative(
              projectRoot,
              jsFilePath
            )}\x1b[0m\n`
          );
        } else if (jsChoice === "2") {
          // Create AlpineJS file with component-based structure
          const jsFilePath = path.resolve(componentDir, `${componentName}.js`);
          const alpineJsContent = `document.addEventListener('alpine:init', () => {\n Alpine.data('${componentName.toLowerCase()}', () => ({\n    show: false,\n    init() {\n      console.log('${componentName} initialized');\n    }\n }));\n});`;
          fs.writeFileSync(jsFilePath, alpineJsContent, "utf8");
          console.log(
            `\x1b[32mAlpineJS file created: ${path.relative(
              projectRoot,
              jsFilePath
            )}\x1b[0m\n`
          );
        } else if (jsChoice === "3") {
          console.log("\x1b[33mNo JavaScript file created.\x1b[0m\n"); // Yellow text
        } else {
          console.log(
            "\x1b[31mInvalid choice! Please choose 1 for Vanilla JS, 2 for AlpineJS, or 3 for No JavaScript.\x1b[0m"
          );
          rl.close();
          process.exit(1);
        }

        rl.question(
          "Do you want to be extra lazy and let me generate a template too?\n1. Yes\n2. No\n3. No with a dad joke\nEnter 1, 2, or 3: ",
          async (templateChoice) => {
            if (templateChoice === "2" || templateChoice === "3") {
              if (templateChoice === "3") {
                const joke = await fetchDadJoke();
                console.log(joke + "\n");
              }
              await endScript(rl);
              process.exit(1);
            }
            // Prompt user to select a template group
            rl.question("Enter the template group: ", async (groupName) => {
              if (!groupName) {
                console.log("\x1b[31mGroup name cannot be empty!\x1b[0m");
                rl.close();
                process.exit(1);
              }

              // Define path for the new template
              const templatesDir = path.resolve(
                projectRoot,
                `website/system/user/templates/default_site/${
                  groupName.replace(".group", "") + ".group"
                }`
              );
              const templateName = `_${componentName.toLowerCase()}.html`; // Add `_` prefix
              const templatePath = path.resolve(templatesDir, templateName);

              // Check if the template already exists
              if (fs.existsSync(templatePath)) {
                displayFatal(`Template already exists: ${templatePath}`);
                process.exit(1);
              }

              logWithHeading(
                `Creating ExpressionEngine template in "${groupName}.group/${templateName}"`
              );

              // Create template directory if it doesn't exist
              fs.mkdirSync(templatesDir, { recursive: true });

              let templateAttributes = `class="${componentName.toLowerCase()}"`;

              if (jsChoice === "2") {
                templateAttributes = `x-data="${componentName.toLowerCase()}" ` + templateAttributes;
              }

              // Define template content
              const templateContent = `{!-- @USAGE {embed='${groupName}/${templateName.replace(".html","")}' component_name="${componentName.toLowerCase()}"} --}\n<div ${templateAttributes}>\n  <strong>Component: {embed:component_name}</strong>\n</div>`;

              // Write template file
              fs.writeFileSync(templatePath, templateContent, "utf8");

              console.log(`\x1b[32mTemplate created: ${path.relative(projectRoot,templatePath)}\x1b[0m`);
              
              await endScript(rl);
              process.exit(1);
            });
          }
        );
      }
    );
  }
);

async function endScript(rl) {
  

  rl.close();

  // Run the autoload script
  try {
    logWithHeading("Running Autoload Script");

    const { stdout, stderr } = await execPromise("npm run autoload", {
      cwd: projectRoot,
    });

    if (stdout) {
      console.log(`\n\x1b[32mImports generated for js and scss\x1b[0m`); // Green text
    }
    if (stderr) {
      displayFatal(`\x1b[31mAutoload Error:\x1b[0m\n${stderr}`);
    }
  } catch (error) {
    displayFatal(
      "\x1b[31mError running autoload script:\x1b[0m",
      error.message
    ); // Red text
    const joke = await fetchDadJoke();
    console.error(`\x1b[33mDad Joke:\x1b[0m ${joke}`); // Yellow text
    process.exit(1);
  }

  // Display celebration message
  displayCelebration("Component created successfully!");
}