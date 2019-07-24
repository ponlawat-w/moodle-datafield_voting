require(['jquery'], $ => {
   $(document).ready(() => {
       const $typeselect = $('#datafield_voting-displaytype');
       const $texttbody = $('#datafield_voting-displaytype_text');
       const $icontbody = $('#datafield_voting-displaytype_icon');
       const $imagetbody = $('#datafield_voting-displaytype_image');

       const $content = $('#datafield_voting-content');

       const $displaytext = $('#datafield_voting-displaytext');

       const $iconselects = $('.datafield_voting-icon_select');
       const $displayicon = $('#datafield_voting-displayicon');
       const $imageeventtrigger = $('#id_customimage');

       const textupdated = () => {
           $content.val($displaytext.val());
       };

       const iconupdated = () => {
           $content.val($displayicon.val());
       };

       const imageupdated = () => {
           $content.val($('input[name=customimage]').val());
       };

       let contentupdateevent = () => {};

       const changetype = () => {
           switch (parseInt($typeselect.val())) {
               case 1:
                   $texttbody.show();
                   $icontbody.hide();
                   $imagetbody.hide();
                   contentupdateevent = textupdated;
                   break;
               case 2:
                   $texttbody.hide();
                   $icontbody.show();
                   $imagetbody.hide();
                   contentupdateevent = iconupdated;
                   break;
               case 3:
                   $texttbody.hide();
                   $icontbody.hide();
                   $imagetbody.show();
                   contentupdateevent = imageupdated;
                   break;
           }
           contentupdateevent();
       };

       const iconselected = event => {
           const $selected = $(event.currentTarget);
           $displayicon.val($selected.attr('data-value'));
           updateselectedicon();
           contentupdateevent();
       };

       const updateselectedicon = () => {
           for (let i = 0; i < $iconselects.length; i++) {
               const $iconselect = $($iconselects[i]);
               if ($iconselect.attr('data-value') === $displayicon.val()) {
                   $iconselect.addClass('selected');
               } else {
                   $iconselect.removeClass('selected');
               }
           }
       };

       $typeselect.change(() => { changetype(); });


       $displaytext.change(() => { contentupdateevent(); });
       $iconselects.click(iconselected);
       $imageeventtrigger.change(() => { contentupdateevent(); });

       changetype();
       if (parseInt($typeselect.val()) === 2) {
           updateselectedicon();
       }
   });
});
