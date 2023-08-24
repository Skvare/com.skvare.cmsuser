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
        {$form.cmsuser_pattern.html} {help id="id-cmsuser_pattern" title=$form.cmsuser_pattern.label}
        <input class="crm-token-selector big" data-field="cmsuser_pattern" />
        <div class="description">Pattern for username selection. All CiviCRM tokens are supported.</div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_notify.label}</td>
      <td>
        {$form.cmsuser_notify.html} {help id="id-cmsuser_notify" title=$form.cmsuser_notify.label}
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_preferred_language.label}</td>
      <td>
        {$form.cmsuser_preferred_language.html} {help id="id-cmsuser_preferred_language" title=$form.cmsuser_preferred_language.label}
      </td>
    </tr>
    {if $form.cmsuser_cms_roles.html}
    <tr>
      <td class="label">{$form.cmsuser_cms_roles.label}</td>
      <td>
          {$form.cmsuser_cms_roles.html}<br/>
        <div class="description">Assign Roles to newly created users,<br/>
          whether user created immediately during the form submission or through Group or Tag configured for scheduled job.</div>
      </td>
    </tr>
    {/if}
    <tr>
      <td class="label">{$form.cmsuser_create_immediately.label}</td>
      <td>
          {$form.cmsuser_create_immediately.html} {help id="id-cmsuser_create_immediately" title=$form.cmsuser_create_immediately.label}<br/>
        <div class="description"></div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_login_immediately.label}</td>
      <td>
          {$form.cmsuser_login_immediately.html} {help id="id-cmsuser_login_immediately" title=$form.cmsuser_login_immediately.label}<br/>
        <div class="description"></div>
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_allow_existing_user_login.label}</td>
      <td>
          {$form.cmsuser_allow_existing_user_login.html} {help id="id-cmsuser_allow_existing_user_login" title=$form.cmsuser_allow_existing_user_login.label}<br/>
        <div class="description"></div>
      </td>
    </tr>
    {if $form.cmsuser_block_roles_autologin.html}
      <tr>
        <td class="label">{$form.cmsuser_block_roles_autologin.label}</td>
        <td>
            {$form.cmsuser_block_roles_autologin.html}<br/>
          <div class="description">Block auto login to users having these roles.</div>
        </td>
      </tr>
    {/if}
    {if $form.cmsuser_user_fields.html}
    <tr>
      <td class="label">{$form.cmsuser_user_fields.label}</td>
      <td>
          {$form.cmsuser_user_fields.html} {help id="id-cmsuser_user_fields" title=$form.cmsuser_user_fields.label}
           <input class="crm-token-selector big" data-field="cmsuser_user_fields" />
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
        {$form.cmsuser_group_create.html} {help id="id-cmsuser_group_create" title=$form.cmsuser_group_create.label}
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_group_history.label}</td>
      <td>
        {$form.cmsuser_group_history.html} {help id="id-cmsuser_group_history" title=$form.cmsuser_group_history.label}
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_group_reset.label}</td>
      <td>
        {$form.cmsuser_group_reset.html} {help id="id-cmsuser_group_reset" title=$form.cmsuser_group_reset.label}
      </td>
    </tr>
    <tr>
      <td class="label"></td>
      <td>Use Tags to Create users, reset users password.</td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_tag_create.label}</td>
      <td>
        {$form.cmsuser_tag_create.html} {help id="id-cmsuser_tag_create" title=$form.cmsuser_tag_create.label}
      </td>
    </tr>
    <tr>
      <td class="label">{$form.cmsuser_tag_history.label}</td>
      <td>
        {$form.cmsuser_tag_history.html} {help id="id-cmsuser_tag_history" title=$form.cmsuser_tag_history.label}
      </td>
    </tr>

    <tr>
      <td class="label">{$form.cmsuser_tag_reset.label}</td>
      <td>
        {$form.cmsuser_tag_reset.html} {help id="id-cmsuser_tag_reset" title=$form.cmsuser_tag_reset.label}
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>

{include file="CRM/Cmsuser/Form/InsertTokens.tpl"}

