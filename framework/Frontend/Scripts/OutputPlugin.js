import fs from 'fs';
import path from 'path';

function deleteFolderRecursive(folderPath) {
  if (fs.existsSync(folderPath)) {
    const files = fs.readdirSync(folderPath);
    for (const file of files) {
      const currentPath = path.join(folderPath, file);
      
      // Skip deletion of the 'uploads' folder
      if (file === 'uploads' && fs.lstatSync(currentPath).isDirectory()) {
        continue;
      }

      if (fs.lstatSync(currentPath).isDirectory()) {
        deleteFolderRecursive(currentPath);  // Recurse if it's a directory
      } else {
        fs.unlinkSync(currentPath);  // Delete file
      }
    }

    // Remove the folder only if it's empty
    if (fs.readdirSync(folderPath).length === 0) {
      fs.rmdirSync(folderPath);
    }
  }
}

export default {
  name: 'output-plugin',
  apply: 'build',
  generateBundle(options, bundle) {
    // Delete the assets folder before writing new assets
    const assetsDir = path.resolve(options.dir, 'assets');
    deleteFolderRecursive(assetsDir);
  },
};
