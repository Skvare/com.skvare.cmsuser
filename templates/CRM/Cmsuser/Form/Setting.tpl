<div id="help">
  <p>This CiviCRM Utility for accomplishing tasks in bulk by adding a tag/group to one or more contact records. To
    create CMS user or Reset CMS user password.
  </p>
</div>

<div class="crm-block crm-form-block">

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <table class="form-layout">
    <tr>
      <td class="label">{$form.cmsuser_pattern.label}</td>
      <td>
        {$form.cmsuser_pattern.html}
        <div class="description">Pattern for username selection. All CiviCRM tokens are supported.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_notify.label}</td>
      <td>
        {$form.cmsuser_notify.html}
        <div class="description">Whether an email should be sent to the user to notify them of account creation?.</div>
      </td>
    </tr>
    {if $form.cmsuser_cms_roles.html}
    <tr>
      <td class="label">{$form.cmsuser_cms_roles.label}</td>
      <td>
          {$form.cmsuser_cms_roles.html}<br/>
        <div class="description">Assign Roles to newly created user.</div>
      </td>
    </tr>
    {/if}
    <tr>
      <td class="label">{$form.cmsuser_create_immediately.label}</td>
      <td>
          {$form.cmsuser_create_immediately.html}<br/><br/>
        <div class="description">Create CMS user immediately when contact get added to Group Or Tag. Example Add
          Contact to Any Tag through Webform configuration.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_user_fields.label}</td>
      <td>
          {$form.cmsuser_user_fields.html}<br/><br/>
        <div class="description">Map Drupal user fields with CiviCRM field. Add new line after each entry.<br/>
          Example
          <br/>
          field_first_name={ldelim}contact.first_name{rdelim}<br/>
          field_last_name={ldelim}contact.last_name{rdelim}</div>
        {$fieldHtml}
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_group_create.label}</td>
      <td>
        {$form.cmsuser_group_create.html}
        <div class="description">CMS user get created for contacts from this group, once username is created, same contact get removed from this group.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_group_history.label}</td>
      <td>
        {$form.cmsuser_group_history.html}
        <div class="description">Newly created CMS user for contacts from above group, get assigned to this group.</div>
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_group_reset.label}</td>
      <td>
        {$form.cmsuser_group_reset.html}
        <div class="description">Any Contact present in this group, their CMS user password get reseted, then contact get removed after reset.</div>
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_tag_create.label}</td>
      <td>
        {$form.cmsuser_tag_create.html}
        <div class="description">CMS user get created for contacts from this tag, once username is created, contact get removed from this tag.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_tag_history.label}</td>
      <td>
        {$form.cmsuser_tag_history.html}
        <div class="description">Newly created CMS user for contacts from above tag, get assigned to this tag.</div>
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_tag_reset.label}</td>
      <td>
        {$form.cmsuser_tag_reset.html}
        <div class="description">Any Contact present in this tag, their CMS user password get reseted, then contact untagged after reset.</div>
      </td>
    </tr>

  </table>

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
