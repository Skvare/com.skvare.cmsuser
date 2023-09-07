# com.skvare.cmsuser

![Screenshot](/images/screenshot_2.png)

Extension to Create CMS User for Contacts from either Group or Tag; this functionality
runs using Scheduled Job or Immediately using Hook when contact is added to either
group or tag. It's totally based on how you configured the setting form.


The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Requirements

* PHP v7.0+
* CiviCRM (5.0)


## Installation (Web UI)

This extension has not yet been published for installation via the web UI.

## Installation (CLI, Zip)

Sysadmins and developers may download the `.zip` file for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
cd <extension-dir>
cv dl com.skvare.cmsuser@https://github.com/Skvare/com.skvare.cmsuser/archive/main.zip
```

## Installation (CLI, Git)

Sysadmins and developers may clone the [Git](https://en.wikipedia.org/wiki/Git) repo for this extension and
install it with the command-line tool [cv](https://github.com/civicrm/cv).

```bash
git clone https://github.com/Skvare/com.skvare.cmsuser.git
cv en cmsuser
```

## Usage
* `Administer CiviCRM` -> `CMS User Setting` (`/civicrm/admin/cmsuser`)
* Configure the Settings form.
     > Click on the Help icon for more information on each field.

* You can configure this extension for two-way users.
    * Creating immediate users through a post hook when any contact gets added to
      either group or tag.
    * And using scheduled jobs, which fetch the contact from the group and/or tag and create a user for each contact.

* Due to any reason the user is not created, we add an activity record of type
`User Account Creation` with a `Failed` status. We retry up to four attempts after
Contact gets removed from the group or tag itself without creating a user account.

* If a user is created successfully, then a new activity of type `User Account Creation` is created with a `Completed` status.

