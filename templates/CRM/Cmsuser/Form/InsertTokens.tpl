<script type="text/javascript">
  cj('form.{$form.formClass}').data('tokens', {$tokens|@json_encode});
  {literal}

  CRM.$(function($) {
    function insertToken() {
      var
        token = $(this).val(),
        field = $(this).data('field');
      if (field.indexOf('html') < 0) {
        field = textMsgID($(this));
      }
      CRM.wysiwyg.insert('#' + field, token);
      $(this).select2('val', '');
    }

    function textMsgID(obj) {
      field = obj.data('field');
      return field;
    }

    // Initialize token selector widgets
    var form = $('form.{/literal}{$form.formClass}{literal}');
    $('input.crm-token-selector', form)
      .addClass('crm-action-menu fa-code')
      .change(insertToken)
      .crmSelect2({
        data: form.data('tokens'),
        placeholder: '{/literal}{ts escape='js'}Tokens{/ts}{literal}'
      });
    $("div.text").show();

  });

</script>
{/literal}
