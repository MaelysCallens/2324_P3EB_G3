---
name: Experiment Successful Cleanup
about: Use this template to clean up an experiment.
title: "Experiment Cleanup: {EXPERIMENT IDEA ISSUE NUMBER AND TITLE}"
labels: 2.x,feature flag, experiment,experiment cleanup
---

<!-- Title suggestion: [Experiment Name] Successful Cleanup -->

## Summary

The experiment is currently rolled out to 100% of users and has been deemed a success.
The changes need to become an official part of the product.

## Steps

- [ ] Determine whether the feature should apply to Business and/or Enterprise.
- [ ] Determine if UX analytics should be kept as is, removed, or modified.
- [ ] Ensure any relevant documentation has been updated.
- [ ] Check to see if the experiment introduced new design assets. Add them to the appropriate repos and document them if needed.
- [ ] Optional: Migrate experiment to a default enabled [feature flag](https://docs.gitlab.com/ee/development/feature_flags) (@todo replace with DXPR doc) for one milestone and add a changelog. 
- [ ] In the next milestone, [remove the feature flag](https://docs.gitlab.com/ee/development/feature_flags/controls.html#cleaning-up) if applicable.
- [ ] After the flag removal is deployed, clean up the feature/experiment feature flags].
- [ ] Ensure the corresponding [Experiment Rollout](https://gitlab.com/groups/gitlab-org/-/boards/1352542?label_name[]=devops%3A%3Agrowth&label_name[]=growth%20experiment&label_name[]=experiment-rollout) issue is updated

/label ~"type::maintenance" ~"workflow::scheduling" ~"growth experiment" ~"feature flag"
