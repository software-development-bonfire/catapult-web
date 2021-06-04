# Catapult Web

# Installation Steps
1. Clone the project from this repository to any of your directory.
2. Run the following commands:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan make:schema
php artisan migrate:install
php artisan migrate
yarn install
yarn run dev
php artisan serve
```

# For Testing
Run the following commands:
```bash
composer install
yarn install
php artisan serve
yarn run dev
```
# For Developers
1. After cloning, click Git Flow in the SourceTree. Click OK to apply.
2. Use Git Flow to branch out to feature / hotfixes. For bugs, click the 'Branch' button and type 'bug/branch_name' to create the bug branch.

## Reminders for Developers
1. Make a feature branch for every new feature. e.g (feature/branch_name)
2. Make a bug branch for bugs and issues that needs to be fixed. e.g (bug/branch_name)
3. Make a hotfix branch for quick fixes in the 'master' branch. e.g (hotfix/branch_name)

### Pull Requests

1. ALWAYS create a pull request for every branch that needs to be merged in the 'develop' branch.
2. Add 2 reviewers, Jaypee Magsakay (general review) and Christian Detera (for backend).
3. Add appropriate Labels: feature, enhancement, bug.
4. ALWAYS rebase your branch to the latest 'develop' branch and resolve conflicts inside your branch before finalizing your PR.

### Rebasing Conflicts
1. After fixing a conflict during rebasing, stage all the files then Open your terminal, DON'T COMMIT. Type the command to continue the rebase:

```bash
git rebase --continue
```

2. Repeat the steps until the rebase is finished.
3. Force push your branch (if necessary).

When you want to stop and restart the rebasing, type this command:

```bash
git rebase --abort
```
