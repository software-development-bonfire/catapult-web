const fs = require('fs');
const env = require('dotenv');
const chokidar = require('chokidar');

env.config({ path: ".env" });

const destinationParentDirectory = process.env.CDIS_FILES_LOCAL_PATH;
const sourceParentDirectory = process.env.POS_FILES_LOCAL_PATH;

const childDirectory = process.env.POS_DIRECTORIES.split(",");
const daysDelay = parseInt(process.env.FETCHING_LESS_DAYS_ALLOWANCE);
const option = {
    ignoreInitial: (process.env.POS_TO_CDIS_IGNORE_INITIALS == 'true'),
}

let fileNames = 'FetchedPOSFiles.json';
let transfered = JSON.parse(fs.readFileSync(fileNames).toString());

let givenDate = new Date();
    givenDate = givenDate.setDate(givenDate.getDate() - daysDelay);
    
childDirectory.forEach(item => {
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
    
    if (! transfered.file_name.includes(fileName)) {
        fs.stat(srcPath, (error, stats) => {
            if (error) {
                console.log(error);
                return;
            }
            if (stats.birthtime >= givenDate) {
                
                fs.cp(srcPath, destinationParentDirectory + folderName + fileName, (err) => {
                    if (err) {
                        console.log(err);
                        return;
                    }
                    transfered.file_name.push(fileName)
                    fs.writeFileSync(fileNames, JSON.stringify(transfered));
                })
            }
        })
    }
}
