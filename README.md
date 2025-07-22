# com.skvare.cmsuser

![Screenshot](/images/screenshot_2.png)

Managing both CiviCRM contacts and CMS user accounts can feel like maintaining two separate worlds. Members fill out registration forms in CiviCRM, but then you need to manually create their website login accounts. Volunteers join specific groups, but accessing member-only content requires a separate registration process. Event attendees complete CiviCRM forms, but logging into your member portal is a different system entirely.

**What if these two systems could work together automatically?**

## The Challenge of Dual Systems

Most organizations using CiviCRM alongside a content management system like Drupal face a common workflow problem:

- **New members register** through CiviCRM forms but can't immediately access member-only website content
- **Bulk imports** bring in hundreds of contacts, but none have website login capabilities  
- **Event registrants** need access to exclusive resources, requiring manual user account creation
- **Volunteers** assigned to specific groups need different levels of website access
- **Staff members** spend countless hours manually creating user accounts for people who are already in CiviCRM

This disconnect creates friction for your constituents and administrative overhead for your team.

## Introducing Automated User Account Creation

The **CMS Users Extension** eliminates this disconnect by automatically creating CMS user accounts for your CiviCRM contacts based on intelligent criteria you define. Instead of manual processes and frustrated users, you get seamless integration that works behind the scenes.

## Two Powerful Approaches to User Creation

### Real-Time Account Creation
Perfect for immediate access needs:
- **Instant gratification**: New members get immediate website access after joining specific groups or receiving certain tags
- **Webform integration**: Registration forms automatically trigger user account creation
- **Event registration**: Attendees gain instant access to event-specific resources
- **Volunteer onboarding**: New volunteers immediately receive appropriate website permissions

### Scheduled Batch Processing  
Ideal for bulk operations and controlled processing:
- **Bulk import support**: Process hundreds of contacts imported from external systems
- **Scheduled processing**: Create user accounts during off-peak hours to minimize system load
- **Quality control**: Review and process accounts in batches with administrative oversight
- **Resource management**: Spread user creation across time to avoid server overload

## Smart Automation That Actually Works

### Tag and Group-Based Triggers
Define exactly which contacts should receive user accounts:
- **Premium members**: Automatically create accounts for contacts tagged as "Premium Member"
- **Event-specific access**: Generate logins for contacts in "Conference 2024 Attendees" group
- **Volunteer portals**: Create accounts when contacts join volunteer-specific groups
- **Regional access**: Provide website access based on geographic or organizational tags

### Reliable Activity Tracking
Never lose track of account creation status:
- **Success tracking**: Completed user creations generate "User Account Creation" activities with "Completed" status
- **Failure documentation**: Failed attempts create activities with "Failed" status for follow-up
- **Audit trail**: Complete history of all account creation attempts and outcomes

### Robust Error Handling
Built-in resilience for real-world scenarios:
- **Automatic retries**: Up to 4 attempts for failed account creations
- **Smart cleanup**: Contacts are removed from trigger groups/tags after repeated failures to prevent endless retry loops
- **Clear reporting**: Failed attempts are logged with specific error information for troubleshooting

## Real-World Use Cases

### Membership Organizations
**Challenge**: New members complete registration but can't access the member portal immediately.
**Solution**: Tag new members as "Active Member" and automatically create their portal login within minutes.

### Educational Institutions
**Challenge**: Students register for courses through CiviCRM but need separate accounts for online learning platforms.
**Solution**: Group students by course enrollment and batch-create learning platform accounts overnight.

### Event Management
**Challenge**: Conference attendees need access to session materials and networking tools.
**Solution**: Automatically create user accounts when contacts are added to the "Conference Attendees" group.

### Volunteer Coordination
**Challenge**: Different volunteer roles need different website access levels.
**Solution**: Use tags to identify volunteer types and create appropriately permissioned user accounts.

### Member-Only Content
**Challenge**: Premium subscribers should have immediate access to exclusive content.
**Solution**: Real-time account creation ensures premium members can access content immediately after upgrading.

## Configuration Made Simple

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

### Easy Setup Process
1. **Navigate to settings**: Access configuration through **Administer CiviCRM > CMS User Setting** (`/civicrm/admin/cmsuser`)
2. **Choose your approach**: Configure real-time creation, scheduled processing, or both
3. **Define triggers**: Select which groups and tags should trigger user account creation
4. **Set preferences**: Customize user account properties and permissions

### Built-in Help and Guidance
The extension includes comprehensive help documentation for each configuration field, ensuring you can set up the system correctly without guessing about options or consequences.

## Technical Excellence and Reliability

### Proven Compatibility
- **CiviCRM Integration**: Works with CiviCRM 5.39 and higher
- **CMS Support**: Designed for Drupal 7, Drupal 8+, WordPress, Backdrop, Joomla integration
- **Regular Updates**: Maintained and updated regularly.

### Performance Considerations
- **Flexible timing**: Choose between immediate processing for urgent needs or scheduled batches for resource management
- **Error resilience**: Robust retry logic prevents temporary issues from causing permanent failures
- **Activity logging**: Complete audit trail without impacting system performance

## Benefits That Transform Operations

### For Administrators
- **Reduced manual work**: Eliminate hours of manually creating user accounts
- **Improved accuracy**: Automated processes reduce human error in account creation
- **Better oversight**: Activity tracking provides clear visibility into account creation status
- **Simplified workflows**: One system triggers create accounts in another seamlessly

### For Members and Users
- **Immediate access**: No waiting for manual account creation processes
- **Seamless experience**: Registration and account creation happen transparently
- **Fewer barriers**: Reduced friction between expressing interest and accessing resources
- **Consistent experience**: Automated processes ensure every user receives the same treatment

### For Organizations
- **Higher engagement**: Members who can immediately access resources are more likely to remain active
- **Reduced support requests**: Fewer "I can't log in" tickets when accounts are created automatically
- **Improved conversion**: Removing friction between sign-up and access improves member retention
- **Scalable operations**: Handle growth without proportionally increasing administrative overhead

## Get Started Today

The CMS Users Extension represents a fundamental shift from manual, error-prone account management to intelligent, automated user provisioning. Whether you're processing bulk imports, managing event registration, or providing member benefits, this extension eliminates the gap between your CRM and your website.

**Supporting Organizations**  
[Skvare](https://skvare.com/contact)

## Ready to Streamline Your User Management?

Stop forcing your contacts to navigate separate registration processes. Stop manually creating user accounts for people who are already in your system. Let automation handle the technical details while you focus on serving your constituents.

The CMS Users Extension is available now and compatible with CiviCRM 5.39 and higher. Transform your dual-system headache into a seamless, automated workflow that your team and your members will appreciate.

---

**[Contact us](https://skvare.com/contact) for support or to learn more** about implementing automated CMS user account creation in your organization.
