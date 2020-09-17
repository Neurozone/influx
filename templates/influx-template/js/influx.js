$(document).ready(function () {

    // Page settings

    targetThisEvent($('article section:first'), true);
    addEventsButtonLuNonLus();

    $('[data-mark-all-read]').click(function () {
        markAllAsRead($(this));
    });

    // on initialise ajaxready à true au premier chargement de la fonction
    $(window).data('ajaxready', true);
    $('article').append('<div id="loader">' + 'LOADING' + '</div>');
    $(window).data('page', 1);
    $(window).data('nblus', 0);

    if ($(window).scrollTop() == 0) scrollInfini();

    // focus sur l'input du login
    if (document.getElementById('inputlogin')) document.getElementById('inputlogin').focus();
});

function readThis(element, id, from) {
    var parent = $(element).closest('section');
    var nextEvent = $('#' + id).nextAll(':visible').first();
    //sur les éléments non lus
    if (!parent.hasClass('eventRead')) {
        parent.addClass('eventRead');
        addOrRemoveFluxNumber('-');
        if (console && console.log) console.log("/action/read/" + from + "/" + id);
        $.ajax({
            url: "/action/read/" + activeScreen + "/" + id,
            success: function (msg) {
                if (msg.status == 'noconnect') {
                    alert(msg.texte)
                } else {
                    if (console && console.log && msg != "") console.log(msg);
                    switch (activeScreen) {
                        case 'from':
                            // cas de la page d'accueil
                            parent.fadeOut(200, function () {

                                    targetThisEvent(nextEvent, true);

                                // on simule un scroll si tous les events sont cachés
                                if ($('article section:last').attr('style') == 'display: none;') {
                                    $(window).scrollTop($(document).height());
                                }
                            });
                            // on compte combien d'article ont été lus afin de les soustraires de la requête pour le scroll infini
                            $(window).data('nblus', $(window).data('nblus') + 1);
                            break;
                        case 'category':
                        case 'items':
                                targetThisEvent(nextEvent, true);

                            // on compte combien d'article ont été lus afin de les soustraires de la requête pour le scroll infini
                            $(window).data('nblus', $(window).data('nblus') + 1);
                            break;
                        default:
                            // autres cas : favoris, selectedFlux ...

                                targetThisEvent(nextEvent, true);

                            break;
                    }
                }
            }
        });
    }
}

function unReadThis(element, id, from) {
    var activeScreen = $('#pageTop').html();
    var parent = $(element).parent().parent();
    if (parent.hasClass('eventRead')) {
        if (from != 'title') {
            $.ajax({
                url: "/action/unreadContent/" + id,

                success: function (msg) {
                    if (msg.status == 'noconnect') {
                        alert(msg.texte)
                    } else {
                        if (console && console.log && msg != "") console.log(msg);
                        parent.removeClass('eventRead');
                        // on compte combien d'article ont été remis à non lus
                        if ((activeScreen == '') || (activeScreen == 'selectedcategory') || (activeScreen == 'selectedFlux'))
                            $(window).data('nblus', $(window).data('nblus') - 1);

                        addOrRemoveFluxNumber('+');
                    }
                }
            });
        }
    }

}