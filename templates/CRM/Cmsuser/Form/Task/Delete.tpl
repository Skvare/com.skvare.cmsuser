<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
      {ts}Are you sure you want to delete the selected contact(s) user(s)?{/ts}  {ts}This action cannot be undone.{/ts}
  </div>

  {if $form.delete_option.html}
  <table class="form-layout">
    <tr class="crm-contact-task-form-block-delete-user">
      <<td class="label">{$form.delete_option.label}</td>
      <td>{$form.delete_option.html}</td>
    </tr>
  </table>
  {/if}
  <h3>{include file="CRM/Contact/Form/Task.tpl"}</h3>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>
