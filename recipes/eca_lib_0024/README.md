## Recipe: Send email to users of a given role

ID: eca_lib_0024

This model sends an email to all users of a certain role. It contains a view with a contextual filter to limit the list of users to those of the given role.

The creation of this model was recorded with lots of additional explanations (e.g. how to use an existing model as a starting point) and [can be watched here](https://tube.tchncs.de/w/8dZuXYZHmuDTutddrTZUfE).

### Installation

```shell
## Import recipe
composer require drupal-eca-recipe/eca_lib_0024

# Apply recipe with Drush (requires version 13 or later):
drush recipe ../recipes/{{ rawid }}

# Apply recipe without Drush:
cd web && php core/scripts/drupal recipe ../recipes/eca_lib_0024

# Rebuilding caches is optional, sometimes required:
drush cr
```
