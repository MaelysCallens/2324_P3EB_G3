Contributing guidelines
=======================

### Git workflow

1. Every pull request must be linked to an issue, no exceptions
2. Ensure your branch contains logical [atomic commits](https://www.pauline-
   vos.nl/atomic-commits/)
3. Write commit messages following the [alphagov Git styleguide](https://github.
   com/alphagov/styleguides/blob/master/git.md)
4. Pull requests must contain a short description of your solution
5. Branch naming convention: person/target-branch/#issue-description-of-branch.
    1. person — The name of the owner of the branch. For example Jur, Rokaya,
       Shaaer, Denis, etc.
    2. main-branch — A reference to the target branch you want to merge into
    3. #issue — Every branch must be linked to a GitHub issue. Enter the issue
       number here.
    4. description-of-branch — Describe what's inside, for example" fix-for-
       jumping-controls-bug or new-icon-set-for-parameter-definition.
6. Unlike in dxpr_builder repository, we do push artifacts (.css files etc) to
   the repository here. This is because DXPR Theme is released on Drupal.org,
   and Drupal.org does not provide the ability to run our Docker scripts to
   create artifacts on the fly.
7. If the issue defines a "Scope of affected files" do not include changes to
   files not in this list unless absolutely necessary. When you do this you must
   explain why. 

### Code ownership

@jjroelofs is the code owner in this repository and no pull requests can be
merged without his review

### Coding Standards

1. [Drupal coding standards](https://www.drupal.org/docs/develop/standards)
2. [Airbnb Javascript coding standards](https://github.com/airbnb/javascript)
   with [some exceptions](https://github.com/dxpr/dxpr_theme/blob/2.x/.eslintrc
   #L25)
3. Compatibility with PHP [7.1 and higher](https://github.com/dxpr/dxpr_builder
   /blob/1.x/scripts/run_drupal-lint.sh#L9)

Coding standards are automatically checked when you create a Pull Request. You
can run code linters locally as well using instructions here:
https://github.com/dxpr/dxpr_theme/blob/2.x/README.md#how-to-run-eslint-check
