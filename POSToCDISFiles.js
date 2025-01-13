const fs = require('fs');
const env = require('dotenv');
const chokidar = require('chokidar');

env.config({ path: ".env" });

const destinationParentDirectory = process.env.CDIS_FILES_LOCAL_PATH;
const sourceParentDirectory = process.env.POS_FILES_LOCAL_PATH;

const ChildDirectory = process.env.POS_DIRECTORIES.split(",");

const option = {
    ignoreInitial: process.env.POS_TO_CDIS_IGNORE_INITIALS,
}
    
ChildDirectory.forEach(item => {
    chokidar.watch(sourceParentDirectory + item, option)
        .on('add', path => copyFile(path, item))
        .on('change', path => copyFile(path, item))
});

function copyFile(srcFilePath, folderName) {
    
    let srcPath = srcFilePath.replace(/\\/g, '/');
    let fileName = srcPath.replace(sourceParentDirectory+folderName, '');
    
    if (folderName == 'RECEIPTS/') {
        fileName = fileName.substring(fileName.indexOf("/") + 1)
    }
    
    fs.cp(srcPath, destinationParentDirectory + folderName + fileName, (err) => {
        if (err) {
            console.log(err);
        }
    })
}