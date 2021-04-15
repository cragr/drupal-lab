const chalk = require('chalk');
const fs = require('fs');
const log = require('./log');
const compile = require('./compile');

/* THIS FUNCTION IS JUST FOR DEBUGGING */
function stringDiff(str1, str2){
  let diff= "";
  str2.split('').forEach(function(val, i){
    if (val != str1.charAt(i))
      diff += val ;
  });
  return diff;
}

module.exports = (filePath) => {
  log(`'${filePath}' is being checked.`);
  // Transform the file.
  compile(filePath, function check(code) {
    const fileName = filePath.slice(0, -9);
    fs.readFile(`${fileName}.css`, function read(err, data) {
      if (err) {
        log(chalk.red(err));
        process.exitCode = 1;
        return;
      }
      if (code !== data.toString()) {
        log(chalk.red(`'${filePath}' is not updated. ${stringDiff(code, data.toString())}`));
        process.exitCode = 1;
      }
    });
  });
};
