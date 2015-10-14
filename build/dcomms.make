; GovCMS

; Core version
; The make file always begins by specifying the core version of Drupal for
; which each package must be compatible.
core = 7.x

; API version
; The make file must specify which Drush Make API version it uses.
api = 2

; Core.
projects[drupal][version] = 7.38
projects[drupal][patch][] = https://www.drupal.org/files/issues/drupal-7.x-allow_profile_change_sys_req-1772316-28.patch
projects[drupal][patch][] = https://www.drupal.org/files/issues/drupal-1470656-26.patch
projects[drupal][patch][] = https://www.drupal.org/files/issues/core-111702-99-use_replyto.patch

; Distro.
projects[govcms][version] = 2.x-dev
