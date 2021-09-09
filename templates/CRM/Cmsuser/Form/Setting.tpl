<div id="help">
  <p>This CiviCRM Utility for accomplishing tasks in bulk by adding a tag/group to one or more contact records. To create a CMS user or Reset CMS user password.</p>
</div>

<div class="crm-block crm-form-block">

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <table class="form-layout">
    <tr>
      <td class="label">{$form.cmsuser_pattern.label}</td>
      <td>
        {$form.cmsuser_pattern.html} {help id="id-cmsuser_pattern"}
        <div class="description">Pattern for username selection. All CiviCRM tokens are supported.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_notify.label}</td>
      <td>
        {$form.cmsuser_notify.html} {help id="id-cmsuser_notify"}
      </td>
    </tr>
    {if $form.cmsuser_cms_roles.html}
    <tr>
      <td class="label">{$form.cmsuser_cms_roles.label}</td>
      <td>
          {$form.cmsuser_cms_roles.html}<br/>
        <div class="description">Assign Roles to newly created users.</div>
      </td>
    </tr>
    {/if}
    <tr>
      <td class="label">{$form.cmsuser_create_immediately.label}</td>
      <td>
          {$form.cmsuser_create_immediately.html} {help id="id-cmsuser_create_immediately"}<br/>
        <div class="description"></div>
      </td>
    </tr>
    {if $form.cmsuser_user_fields.html}
    <tr>
      <td class="label">{$form.cmsuser_user_fields.label}</td>
      <td>
          {$form.cmsuser_user_fields.html} {help id="id-cmsuser_user_fields"}
          <br/><br/>
        {ts}List of Custom Fields available to users.{/ts}
        {$fieldHtml}
        <br/><br/><br/>
      </td>
    </tr>
    {/if}

    <tr>
      <td class="label"></td>
      <td>Use Groups to Create users, reset users password.</td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_group_create.label}</td>
      <td>
        {$form.cmsuser_group_create.html} {help id="id-cmsuser_group_create"}
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_group_history.label}</td>
      <td>
        {$form.cmsuser_group_history.html} {help id="id-cmsuser_group_history"}
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_group_reset.label}</td>
      <td>
        {$form.cmsuser_group_reset.html} {help id="id-cmsuser_group_reset"}
      </td>
    </tr>
    <tr>
      <td class="label"></td>
      <td>Use Tags to Create users, reset users password.</td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_tag_create.label}</td>
      <td>
        {$form.cmsuser_tag_create.html} {help id="id-cmsuser_tag_create"}
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_tag_history.label}</td>
      <td>
        {$form.cmsuser_tag_history.html} {help id="id-cmsuser_tag_history"}
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_tag_reset.label}</td>
      <td>
        {$form.cmsuser_tag_reset.html} {help id="id-cmsuser_tag_reset"}
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>
