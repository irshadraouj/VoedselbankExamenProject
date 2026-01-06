# Default Project

## Introduction

The **Default Project** is a structured web development setup that combines backend and frontend tools to streamline development. It includes automation for managing components, a flexible folder structure, and a local development environment powered by `.ddev`.

## Setup Instructions

### Prerequisites

Ensure you have the following installed:

- [Docker](https://docker.com)
- [DDEV](https://ddev.com/) (Includes install guides per OS)
- [Composer](https://getcomposer.org/)
- For windows: [WSL](https://learn.microsoft.com/en-us/windows/wsl/install)

### Installation Steps

There are several predefined installation steps combined in composer scripts, check the composer.json file for more information.
Use `composer run` followed by the script available in the composer.json file.
Running all scripts at once may trigger a timeout. If that happens, run them individually as mentioned below.

These are the steps to setup a full ExpressionEngine installation front- and back-end

1. Install composer dependencies
   ```bash
   composer install
   ```   
2. Configure and install the local ddev development environment:
   ```bash
   composer run setup:ddev
   ```
3. Sets up the entire ExpressionEngine installation with the version and other variables specified inside .ddev/.env:
   Any missing variables will be prompted.
   ```bash
   composer run setup:website
   ```
4. Expose ports for frontend
   Add the following to the .ddev/config.yaml file to expose ports for vitejs
   ```
   web_extra_exposed_ports:
    - name: vite
      container_port: 5173
      http_port: 5172
      https_port: 5173
   ```
   
5. DDEV Restart
   For changes to take effect
   ```
   ddev restart
   ```
## Expression Engine Development

This frameworks CLI contains an installation script that works with the ddev configuration. `php ./cli install`. this wil setup ExpressionEngine and reorganize the files, the version is specified in the .env file. This is included in the `composer run setup:website` script. 

Wyvern:5ca17de6-2b2b-474b-9763-69182f6ff3f4

## Frontend Development Workflow

### Overview

The frontend is fully integrated with ExpressionEngine templates. The default module handles the imports for the css files. It is required to output the manifest.json for this. When setup properly use `ddev npm run dev` and refresh the browser to enable vite's Hot Module Replacement (HMR) behaviour.   
```
  <!-- Favicon & Manifest -->
  {exp:dunique:vite src="manifest.webmanifest"}
  <!-- Styles -->
  {exp:dunique:vite src="resources/base.css|resources/styles.scss"}
  <!-- Script -->
  {exp:dunique:vite src="resources/main.js"}
```
This section outlines how to set up and manage components. It includes instructions for adding new components, using `npm run autoload` to streamline imports, and following a development workflow.

### Adding New Components

1. **Create Component Files**

   Add component-specific files in `resources/components/`. Each component should have its own folder with a `.css`, `.scss` and/or `.js` file. these files are automatically added to vite's entrypoint. .css files are included in the tailwind configuration file giving the ability to use tailwind functions.

2. **Run Autoload**
   Use `npm run autoload` to automatically generate import statements for CSS, SCSS, JavaScript files:
   ```bash
   npm run autoload
   ```
   This ensures all new components are included in the build process.

### Create a New Component with `npm run make:component`

Of course this process is automated aswell, use the following command to create a new component:

```bash
npm run make:component [component_name]
```

This will:

- Create a new folder for the component in `resources/components/`.
- Generates templates for CSS, SCSS, and JavaScript files.
- Prompt you to setup the desired component configuration.

### DDEV Commands

1. **Start the environment**
   ```bash
   ddev start
   ```
2. **Access the project locally**
   ```bash
   ddev launch
   ```
3. **Stop the environment**
   ```bash
   ddev stop
   ```
4. **Check logs**
   ```bash
   ddev logs
   ```
5. **Run database commands**
   ```bash
   ddev mysql
   ```
   **SSH into environment**
   ```bash
   ddev ssh
   ```
   **Running npm inside the environment**
   ```bash
   ddev npm
   ```

## Folder Structure

### Overview

The **Default Project** has a clear folder structure to separate backend tools, frontend resources, and deployment assets.

```
/.ddev/            # Local development environment configuration
/framework/        # Backend development tools
/resources/        # Frontend assets
  _functions/  # Scss unprocessed functions
  base/        # Global styles
  components/  # Reusable web components
  config/      # SCSS variables and mixins
  fonts/       # Custom fonts
  icons/       # Icons for icon fonts
  images/      # Project images
  base.css     # Base tailwind configuration and variables
  main.js      # JavaScript entry point
  styles.scss  # Main SCSS file
/website/          # Deployment files
  public_html/ # Publicly accessible files
  system/      # ExpressionEngine CMS core
  config.php   # ExpressionEngine config
.gitignore         # Git exclusions
cli                # CLI utilities
composer.json      # PHP dependencies
package.json       # Node.js dependencies
README.md          # Project documentation
vite.config.js     # Vite build configuration
```

## Framework CLI Utilities

This project contains some custom command-line interface utilities to speed things up.
The cli is combined with the ddev environment and uses it's command-line options.
To get a list of available commands you can run `ddev php ./cli list`

Try `ddev php ./cli example` to get a random quote