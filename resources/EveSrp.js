
import $ from 'jquery';

$(function () {
    initPopover();
});

function initPopover() {
    $('body').on('click', '.delete-division', function (evt) {
        const classList = $(evt.target).attr('class').split(/\s+/);
        let id;
        for (let i = 0; i < classList.length; i++) {
            if (classList[i].indexOf('id-') === 0) {
                id = classList[i].substr(3);
                break;
            }
        }
        if (id) {
            $('form[name="delete-division-'+id+'"]').submit();
        }
    });
}
