#!/bin/bash
# Clean leaked files from git history - DESTRUCTIVE - backup first!
# Requires: git filter-repo (pip install git-filter-repo)

set -e
echo "This will rewrite history. Backup your repo first!"
read -p "Continue? (y/N) " confirm
[[ $confirm != "y" ]] && exit 1

git filter-repo --path .config --path .local --path .subversion --path public/uploads --path uploads --path original/uploads --path luntiang-hapag --path .sudo_as_admin_successful --invert-paths --force

echo "History cleaned. Now add fixed .gitignore and push --force"
echo "cp FIXED/.gitignore ./"
echo "git add .gitignore"
echo "git commit -m 'fix: add proper gitignore and remove leaked PII'"
echo "git push origin main --force"
