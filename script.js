require(['jquery'], function($) {
    $(document).ready(() => {

        const apiurl = `${M.cfg.wwwroot}/mod/data/field/voting/api.php`;
        const sesskey = M.cfg.sesskey;

        const $areas = $('span.datafield_voting-area');

        $areas.each((index, area) => {
            const $area = $(area);
            const $vote_a = $area.find('a').first();
            const $vote_img = $vote_a.find('img').first();
            const $totalvotes_span = $area.find('span.totalvotes').first();

            const fieldid = $area.attr('data-fieldid');
            const recordid = $area.attr('data-recordid');

            $vote_a.click(() => {
                $totalvotes_span.removeClass('text-danger');
                $totalvotes_span.html('…');
                $.post(apiurl, {
                    sesskey: sesskey,
                    fieldid: fieldid,
                    recordid: recordid,
                    action: 'submitvote'
                }, response => {
                    $totalvotes_span.html(response.totalvotes);
                    $vote_img.attr('src', M.util.image_url(
                        response.haveivoted ? 'thumbsup' : 'thumbsup-no',
                        'datafield_voting'
                    ));
                }).fail(response => {
                    let errorinfo = response.responseText;
                    if (response.responseJSON) {
                        errorinfo = response.responseJSON.error;
                    }
                    $totalvotes_span.html(errorinfo)
                        .addClass('text-danger');
                });
            });
        });
    });
});
