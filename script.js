require(['jquery'], function($) {
    $(document).ready(() => {

        const apiurl = `${M.cfg.wwwroot}/mod/data/field/voting/api.php`;
        const sesskey = M.cfg.sesskey;

        const $areas = $('span.datafield_voting-area');

        $areas.each((index, area) => {
            const $area = $(area);
            const $buttonspan = $area.find('.datafield_voting-buttonspan');
            const $totalvotesspan = $area.find('span.totalvotes').first();

            const applyaction = () => {
                const $button = $buttonspan.find('a,button').first();

                const fieldid = $area.attr('data-fieldid');
                const recordid = $area.attr('data-recordid');

                $button.click(() => {
                    $totalvotesspan.removeClass('text-danger');
                    $totalvotesspan.html('…');
                    $.post(apiurl, {
                        sesskey: sesskey,
                        fieldid: fieldid,
                        recordid: recordid,
                        action: 'submitvote'
                    }, response => {
                        $buttonspan.html(response.buttonhtml);
                        $totalvotesspan.html(response.totalvotes);
                        applyaction();
                    }).fail(response => {
                        let errorinfo = response.responseText;
                        if (response.responseJSON) {
                            errorinfo = response.responseJSON.error;
                        }
                        $totalvotesspan.html(errorinfo)
                            .addClass('text-danger');
                    });
                });
            };
            applyaction();
        });
    });
});
