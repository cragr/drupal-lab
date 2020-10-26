#!/bin/bash
#
# This script performs code quality checks.
#
# The script makes the following checks:
# - Spell checking.
# - File modes.
# - No changes to core/node_modules directory.
# - PHPCS checks php and yaml files.
# - Eslint checks javascript files.
# - Checks .es6.js and .js files are equivalent.
# - Stylelint checks css files.
# - Checks .pcss.css and .css files are equivalent.

# cSpell:disable

# Searches an array.
contains_element() {
  local e
  for e in ${@:2}; do [[ "$e" == "$1" ]] && return 0; done
  return 1
}

# Set up variables to make coloured output simple.
red=$(tput setaf 1 && tput bold)
green=$(tput setaf 2)
reset=$(tput sgr0)

CACHED=0
while test $# -gt 0; do
  case "$1" in
    -h|--help)
      echo "Drupal code quality checks"
      echo " "
      echo "options:"
      echo "-h, --help                show brief help"
      echo "--cached                  checks staged files"
      exit 0
      ;;
    --cached)
      CACHED=1
      shift
      ;;
    *)
      break
      ;;
  esac
done

# Gets list of files to check.
if [[ "$CACHED" == "0" ]]; then
  # For DrupalCI / default behaviour this is the list of all changes in the
  # working directory.
  FILES=$(git ls-files --other --modified --exclude-standard --exclude=vendor)
else
  # Check staged files only.
  if git rev-parse --verify HEAD >/dev/null 2>&1
  then
    AGAINST=HEAD
  else
    # Initial commit: diff against an empty tree object
    AGAINST=4b825dc642cb6eb9a060e54bf8d69288fbee4904
  fi
  FILES=$(git diff --cached --name-only $AGAINST);
fi

TOP_LEVEL=$(git rev-parse --show-toplevel)

# Build up a list of absolute file names.
ABS_FILES=
for FILE in $FILES; do
  ABS_FILES="$ABS_FILES $TOP_LEVEL/$FILE"
done

# Exit early if there are no files.
if [[ "$ABS_FILES" == "" ]]; then
  printf "There are no files to check. If you have staged a commit use the --cached option.\n"
  exit;
fi;

# This script assumes that composer install and yarn install have already been
# run and all dependencies are updated.
FINAL_STATUS=0

# Check all files for spelling in one go for better performance.
cd "$TOP_LEVEL/core"
printf "SPELLCHECK\n"
printf -- '-%.0s' {1..100}
printf "\n"
yarn run -s spellcheck -c $TOP_LEVEL/core/.cspell.json $ABS_FILES
if [ "$?" -ne "0" ]; then
  # If there are failures set the status to a number other than 0.
  FINAL_STATUS=1
  printf "\nCSPELL: ${red}failed${reset}\n\n\n"
else
  printf "\nCSPELL: ${green}passed${reset}\n\n\n"
fi
cd "$TOP_LEVEL"

for FILE in $FILES; do
  printf "\nCHECKING: %s\n" "$FILE"
  printf -- '-%.0s' {1..100}
  printf "\n"
  STATUS=0;

  # Ensure the file still exists (i.e. is not being deleted).
  if [ -a $FILE ]; then
    if [ ${FILE: -3} != ".sh" ]; then
      # Ensure the file has the correct mode.
      STAT="$(stat -f "%A" $FILE 2>/dev/null)"
      if [ $? -ne 0 ]; then
        STAT="$(stat -c "%a" $FILE 2>/dev/null)"
      fi
      if [ "$STAT" -ne "644" ]; then
        printf "${red}git pre-commit check failed:${reset} file $FILE should be 644 not $STAT\n"
        STATUS=1
      fi
    fi
  fi

  # Don't commit changes to vendor.
  if [[ "$FILE" =~ ^vendor/ ]]; then
    printf "${red}git pre-commit check failed:${reset} file in vendor directory being committed ($FILE)\n"
    STATUS=1
  fi

  # Don't commit changes to core/node_modules.
  if [[ "$FILE" =~ ^core/node_modules/ ]]; then
    printf "${red}git pre-commit check failed:${reset} file in core/node_modules directory being committed ($FILE)\n"
    STATUS=1
  fi

  ############################################################################
  ### PHP AND YAML FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.(inc|install|module|php|profile|test|theme|yml)$ ]]; then
    # Test files with phpcs rules.
    vendor/bin/phpcs "$TOP_LEVEL/$FILE" --runtime-set installed_paths "$TOP_LEVEL/vendor/drupal/coder/coder_sniffer" --standard="$TOP_LEVEL/core/phpcs.xml.dist"
    PHPCS=$?
    if [ "$PHPCS" -ne "0" ]; then
      # If there are failures set the status to a number other than 0.
      STATUS=1
    else
      printf "PHPCS: $FILE ${green}passed${reset}\n"
    fi
  fi

  ############################################################################
  ### JAVASCRIPT FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.js$ ]] && [[ ! $FILE =~ ^core/tests/Drupal/Nightwatch ]] && [[ ! $FILE =~ ^core/assets/vendor/jquery.ui/ui ]]; then
    # Work out the root name of the Javascript so we can ensure that the ES6
    # version has been compiled correctly.
    if [[ $FILE =~ \.es6\.js$ ]]; then
      BASENAME=${FILE%.es6.js}
      COMPILE_CHECK=1
    else
      BASENAME=${FILE%.js}
      # We only need to compile check if the .es6.js file is not also
      # changing. This is because the compile check will occur for the
      # .es6.js file. This might occur if the compile scripts have changed.
      contains_element "$BASENAME.es6.js" "${FILES[@]}"
      HASES6=$?
      if [ "$HASES6" -ne "0" ]; then
        COMPILE_CHECK=1
      else
        COMPILE_CHECK=0
      fi
    fi
    if [[ "$COMPILE_CHECK" == "1" ]] && [[ -f "$TOP_LEVEL/$BASENAME.es6.js" ]]; then
      cd "$TOP_LEVEL/core"
      yarn run build:js --check --file "$TOP_LEVEL/$BASENAME.es6.js"
      CORRECTJS=$?
      if [ "$CORRECTJS" -ne "0" ]; then
        # No need to write any output the yarn run command will do this for
        # us.
        STATUS=1
      fi
      # Check the coding standards.
      if [[ -f ".eslintrc.passing.json" ]]; then
        node ./node_modules/eslint/bin/eslint.js --quiet --config=.eslintrc.passing.json "$TOP_LEVEL/$BASENAME.es6.js"
        CORRECTJS=$?
        if [ "$CORRECTJS" -ne "0" ]; then
          # No need to write any output the node command will do this for us.
          STATUS=1
        fi
      fi
      cd $TOP_LEVEL
    else
      # If there is no .es6.js file then there should be unless the .js is
      # not really Drupal's.
      if ! [[ "$FILE" =~ ^core/assets/vendor ]] && ! [[ "$FILE" =~ ^core/scripts/js ]] && ! [[ "$FILE" =~ ^core/scripts/css ]] && ! [[ "$FILE" =~ core/postcss.config.js ]] && ! [[ -f "$TOP_LEVEL/$BASENAME.es6.js" ]]; then
        printf "${red}FAILURE${reset} $FILE does not have a corresponding $BASENAME.es6.js\n"
        STATUS=1
      fi
    fi
  elif [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.js$ ]] && [[ $FILE =~ ^core/assets/vendor/jquery.ui/ui ]]; then
    ## Check for minified file changes.
    if [[ $FILE =~ -min\.js$ ]]; then
      BASENAME=${FILE%-min.js}
      contains_element "$BASENAME.js" "${FILES[@]}"
      HASSRC=$?
      if [ "$HASSRC" -ne "0" ]; then
        COMPILE_CHECK=1
      else
        ## Source was also changed and will be checked.
        COMPILE_CHECK=0
      fi
    else
      ## Check for source changes.
      BASENAME=${FILE%.js}
      COMPILE_CHECK=1
    fi
    if [[ "$COMPILE_CHECK" == "1" ]] && [[ -f "$TOP_LEVEL/$BASENAME.js" ]]; then
      cd "$TOP_LEVEL/core"
      yarn run build:jqueryui --check --file "$TOP_LEVEL/$BASENAME.js"
      CORRECTJS=$?
      if [ "$CORRECTJS" -ne "0" ]; then
        # The yarn run command will write any error output.
        STATUS=1
      fi
      cd $TOP_LEVEL
    else
      # If there is no .js source file
      if ! [[ -f "$TOP_LEVEL/$BASENAME.js" ]]; then
        printf "${red}FAILURE${reset} $FILE does not have a corresponding $BASENAME.js\n"
        STATUS=1
      fi
    fi
  else
    # Check coding standards of Nightwatch files.
    if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.js$ ]]; then
      cd "$TOP_LEVEL/core"
      # Check the coding standards.
      if [[ -f ".eslintrc.passing.json" ]]; then
        node ./node_modules/eslint/bin/eslint.js --quiet --config=.eslintrc.passing.json "$TOP_LEVEL/$FILE" | indent
        CORRECTJS=$?
        if [ "$CORRECTJS" -ne "0" ]; then
          # No need to write any output the node command will do this for us.
          STATUS=1
        fi
      fi
      cd $TOP_LEVEL
    fi
  fi

  ############################################################################
  ### CSS FILES
  ############################################################################
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.css$ ]]; then
    # Work out the root name of the CSS so we can ensure that the PostCSS
    # version has been compiled correctly.
    if [[ $FILE =~ \.pcss\.css$ ]]; then
      BASENAME=${FILE%.pcss.css}
      COMPILE_CHECK=1
    else
      BASENAME=${FILE%.css}
      # We only need to compile check if the .pcss.css file is not also
      # changing. This is because the compile check will occur for the
      # .pcss.css file. This might occur if the compiled stylesheets have
      # changed.
      contains_element "$BASENAME.pcss.css" "${FILES[@]}"
      HASPOSTCSS=$?
      if [ "$HASPOSTCSS" -ne "0" ]; then
        COMPILE_CHECK=1
      else
        COMPILE_CHECK=0
      fi
    fi
    # PostCSS
    if [[ "$COMPILE_CHECK" == "1" ]] && [[ -f "$TOP_LEVEL/$BASENAME.pcss.css" ]]; then
      cd "$TOP_LEVEL/core"
      yarn run build:css --check --file "$TOP_LEVEL/$BASENAME.pcss.css"
      CORRECTCSS=$?
      if [ "$CORRECTCSS" -ne "0" ]; then
        # No need to write any output the yarn run command will do this for
        # us.
        STATUS=1
      fi
      cd $TOP_LEVEL
    fi
  fi
  if [[ -f "$TOP_LEVEL/$FILE" ]] && [[ $FILE =~ \.css$ ]] && [[ -f "core/node_modules/.bin/stylelint" ]]; then
    BASENAME=${FILE%.css}
    # We only need to use stylelint on the .pcss.css file. So if this css file
    # has a corresponding .pcss don't do styleint.
    if [[ $FILE =~ \.pcss\.css$ ]] || [[ ! -f "$TOP_LEVEL/$BASENAME.pcss.css" ]]; then
      cd "$TOP_LEVEL/core"
      node_modules/.bin/stylelint "$TOP_LEVEL/$FILE"
      if [ "$?" -ne "0" ]; then
        STATUS=1
      else
        printf "STYLELINT: $FILE ${green}passed${reset}\n"
      fi
      cd $TOP_LEVEL
    fi
  fi

  if [[ "$STATUS" == "1" ]]; then
    FINAL_STATUS=1
    printf "\n%s ${red}failed${reset}\n" "$FILE"
  else
    printf "\n%s ${green}passed${reset}\n" "$FILE"
  fi

  printf "\n\n\n"
done

exit $FINAL_STATUS
