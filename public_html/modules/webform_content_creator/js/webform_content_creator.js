(function($, Drupal) {
  $(document).ready(function() {
    function validateContentType(element) {
      if (!element) {
        return;
      }
      var n = element.id.lastIndexOf("-content-type-field");
      if (!n) {
        return;
      }
      var webformField = element.id.substring(0,n).concat('-webform-field');
      var customValue = element.id.substring(0,n).concat('-custom-value');
      var customCheck = element.id.substring(0,n).concat('-custom-check');
      if (element.checked) {
        if (webformField) {
          $('#'.concat(webformField)).prop("disabled", false);
        }
      } else {
        if (webformField) {
          $('#'.concat(webformField)).prop("disabled", true);
        }
        if (customValue) {
          $('#'.concat(customValue)).prop("disabled", true);
          $('#'.concat(customValue)).addClass("webform-content-creator disabled");
          $('#'.concat(customValue)).val("");
        }
        if (customCheck) {        
          $('#'.concat(customCheck)).prop("checked", false);
        }
      }
    }
    
    function validateCustomCheck(element) {
      if (!element) {
        return;
      }

      if ($('#'.concat(element.id)).is(':disabled')) {
        return;
      }
      var n = element.id.lastIndexOf("-custom-check");
      if (!n) {
        return;
      }
      var webformField = element.id.substring(0,n).concat('-webform-field');
      var customValue = element.id.substring(0,n).concat('-custom-value');
      if (element.checked) {
        if (webformField) {
          $('#'.concat(webformField)).prop("disabled", true);
        }
        if (customValue) {
          $('#'.concat(customValue)).prop("disabled", false);
          $('#'.concat(customValue)).removeClass("webform-content-creator disabled");
        }
      } else {
        if (webformField) {
          $('#'.concat(webformField)).prop("disabled", false);
        }
        if (customValue) {
          $('#'.concat(customValue)).prop("disabled", true );
          $('#'.concat(customValue)).addClass("webform-content-creator disabled");
        }
      }      
    }
    
    $('[name$="[content_type_field]"]').each(function() {
      validateContentType(this);
    });

    $('[name$="[content_type_field]"]').click(function() {
      validateContentType(this);
    });

    $('[name$="[custom_check]"]').each(function() {
      validateCustomCheck(this);
    });

    $('[name$="[custom_check]"]').click(function() {
      validateCustomCheck(this);
    });
  
  });
})(jQuery, Drupal);
