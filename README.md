# com.skvare.cmsuser

![Screenshot](/images/screenshot_2.png)

Extension to Create CMS user for Contacts from either Group or Tag, this 
functionality runs using Scheduled Job or Immediately using hook when contact
is added to either group or tag.  It's totally based on how you configured 
the setting form.


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
* Configure the Setting form.
     > Click on Help icon for more additional information of each field.

* You can configure this extension for 2 way users.
    * Creating Immediate users through a post hook when any contact gets added to 
either Group or Tag.

    * And using Scheduled Jobs, which fetch the contact from Group and/or Tag, and 
create a user for each contact.

* Due to any reason the user is not created then we add an Activity record of type 
`User Account Creation` with `Failed` status. We retry upto 4 attempt, there 
after Contact gets removed from the Group/Tag itself without creating a user account.

* If a user is created successfully then a new activity of type `User Account 
Creation` is created with `Completed` Status.

