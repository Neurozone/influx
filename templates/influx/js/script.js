var keyCode = new Array();

keyCode['shift'] = 16;
keyCode['ctrl'] = 17;
keyCode['enter'] = 13;
keyCode['l'] = 76;
keyCode['m'] = 77;
keyCode['s'] = 83;
keyCode['n'] = 78;
keyCode['v'] = 86;
keyCode['p'] = 80;
keyCode['k'] = 75;
keyCode['o'] = 79;
keyCode['h'] = 72;
keyCode['j'] = 74;
keyCode['space'] = 32;

$(document).ready(function () {

    // Page settings
    if ($('.settings').length) {

        // Si nom du bloc en hash dans url
        var hash = window.location.hash;
        if (hash.length) {
            toggleBlocks(hash);
        }

        // Affichage des differents blocs apres clic sur le menu
        $('.toggle').click(function () {
                toggleBlocks($(this).attr("href"));
            }
        );

        $('[data-zone="installation"] form').submit(function (event) {
            var form = $(this);
            installPlugin(form.find('[name="zip"]').val(), form);
            event.preventDefault();
        });

        $('[data-otp-generate]').click(function () {
            var otpGeneratorEl = $(this);
            randomOtpSecret($(otpGeneratorEl.data('otp-generate')), $(otpGeneratorEl.data('otp-qrcode')));
        })

    } else {

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
    }

    $('[data-toggle-group]').click(function () {
        toggleTab($(this));
    });


    // focus sur l'input du login
    if (document.getElementById('inputlogin')) document.getElementById('inputlogin').focus();
});


function maj(data) {
    server = data.maj["leed"];
    if (server != null && server.version != null && server.version != $(".versionBloc").html()) {
        $(".versionBloc").addClass('newVersion');
        $('.versionBloc').attr('title', 'Version ' + server.version + ' disponible.');
        if (server.link != null) $('.versionBloc').attr('onclick', 'window.location="' + server.link + '";');
    }
}

/*
function _t(key,args){
    var value = i18n[key];
    if(typeof(value)!=='undefined'){
        if(args!=null){
            for(i=0;i<args.length;i++){
                value = value.replace('$'+(i+1),args[i]);
            }
        }
    } else {
        value = key;
    }
    return value;
}
*/


$(document).keydown(function (e) {
    switch (true) {
        case e.altKey || e.ctrlKey || e.shiftKey || e.metaKey:
        case $('.index').length == 0:
        case $("input:focus").length != 0:
            return true;
    }
    switch (e.which) {

        case keyCode['m']:
            //marque l'élément sélectionné comme lu / non lu
            readTargetEvent();
            return false;
            break;

        case keyCode['l']:
            //marque l'élément precédent comme non lu et réafficher
            targetPreviousEventRead();
            return false;
            break;

        case keyCode['s']:
            //marque l'élément sélectionné comme favori / non favori
            switchFavoriteTargetEvent();
            return false;
            break;
        case keyCode['n']:
            //élément suivant (sans l'ouvrir)
            targetNextEvent();
            return false;
            break;
        case keyCode['v']:
            //ouvre l'url de l'élément sélectionné
            openTargetEvent();
            return false;
            break;
        case keyCode['p']:
            //élément précédent (sans l'ouvrir)
            targetPreviousEvent();
            return false;
            break;
        case keyCode['space']:
            //élément suivant (et l'ouvrir)
            targetNextEvent();
            openTargetEvent();
            return false;
            break;
        case keyCode['k']:
            //élément précédent (et l'ouvrir)
            targetPreviousEvent();
            openTargetEvent();
            return false;
            break;
        case keyCode['o']:
        case keyCode['enter']:
            //ouvrir l'élément sélectionné
            openTargetEvent();
            return false;
            break;
        case keyCode['h']:
            //ouvrir/fermer le panneau d'aide
            $('#helpPanel').fadeToggle(200);
            return false;
            break;
        case keyCode['j']:
            // Affiche / cache les blocs résumé / content
            toggleArticleDisplayMode(document.getElementById('btnDisplayMode_' + $('.eventSelected').attr('id')), $('.eventSelected').attr('id'));
            return false;
            break;
    }
});

function getParameters() {
    var url = window.location.toString();
    var url_split = url.split('/');

    var id = url_split[4].split('#');
    var ret_tab = {"protocol": url_split[0], "root": url_split[2], "action": url_split[3], "id": id[0]}

    return ret_tab;
}

$(window).scroll(function () {
    scrollInfini();
});

function scrollInfini() {
    var deviceAgent = navigator.userAgent.toLowerCase();
    if (console && console.log) console.log(getParameters());

    if ($('.index').length) {
        // On teste si ajaxready vaut false, auquel cas on stoppe la fonction
        if ($(window).data('ajaxready') == false) return;

        if (isIntoView($($('section:last')))) {
            // lorsqu'on commence un traitement, on met ajaxready à false
            $(window).data('ajaxready', false);

            //j'affiche mon loader pour indiquer le chargement
            $('article #loader').show();

            //utilisé pour l'alternance des couleurs d'un article à l'autre
            if ($('article section:last').hasClass('eventHightLighted')) {
                hightlighted = 1;
            } else {
                hightlighted = 2;
            }

            var url_route = getParameters();
            // récupération des variables passées en Get
            var action = url_route["action"];
            var category = getUrlVars()['category'];
            var flux = url_route["id"];
            var order = getUrlVars()['order'];
            if (order) {
                order = '&order=' + order
            } else {
                order = ''
            }

            $.ajax({
                url: '/article/flux',
                type: 'post',
                data: 'scroll=' + $(window).data('page') + '&nblus=' + $(window).data('nblus') + '&hightlighted=' + hightlighted + '&action=' + action + '&category=' + category + '&flux=' + flux + order,

                //Succès de la requête
                success: function (data) {
                    if (data.replace(/^\s+/g, '').replace(/\s+$/g, '') != '') {    // on les insère juste avant le loader
                        $('article #loader').before(data);
                        //on supprime de la page le script pour ne pas intéragir avec les next & prev
                        $('article .scriptaddbutton').remove();
                        //si l'élement courant est caché, selectionner le premier élément du scroll
                        //ou si le div loader est sélectionné (quand 0 article restant suite au raccourcis M)
                        if (($('article section.eventSelected').attr('style') == 'display: none;')
                            || ($('article div.eventSelected').attr('id') == 'loader')) {
                            targetThisEvent($('article section.scroll:first'), true);
                        }
                        // on les affiche avec un fadeIn
                        $('article section.scroll').fadeIn(600);
                        // on supprime le tag de classe pour le prochain scroll
                        $('article section.scroll').removeClass('scroll');
                        $(window).data('ajaxready', true);
                        $(window).data('page', $(window).data('page') + 1);
                        $(window).data('enCoursScroll', 0);
                        // appel récursif tant qu'un scroll n'est pas detecté.
                        if ($(window).scrollTop() == 0) scrollInfini();
                    } else {
                        $('article #loader').addClass('finScroll');
                    }
                },
                complete: function () {
                    // le chargement est terminé, on fait disparaitre notre loader
                    $('article #loader').fadeOut(400);
                }
            });
        }
    }
};

/* Fonctions de sélections */

/* Cette fonction sera utilisé pour le scroll infini, afin d'ajouter les évènements necessaires */
function addEventsButtonLuNonLus() {
    var handler = function (event) {
        var target = event.target;
        var id = this.id;
        if ($(target).hasClass('readUnreadButton') || $(target).hasClass('icon-eye')) {
            buttonAction(target, id);
        } else {
            targetThisEvent(this);
        }
    }
    // on vire tous les évènements afin de ne pas avoir des doublons d'évènements
    $('article section').unbind('click');
    // on bind proprement les click sur chaque section
    $('article section').bind('click', handler);
}

function targetPreviousEvent() {
    targetThisEvent($('.eventSelected').prevAll(':visible').first(), true);
}

function targetNextEvent() {

    targetThisEvent($('.eventSelected').nextAll(':visible').first(), true);
}

function targetThisEvent(event, focusOn) {
    target = $(event);
    if (target.prop("tagName") == 'SECTION') {
        $('.eventSelected').removeClass('eventSelected');
        target.addClass('eventSelected');
        var id = target.attr('id');
        if (id && focusOn) window.location = '#' + id;
    }
    if (target.prop("tagName") == 'DIV') {
        $('.eventSelected').removeClass('eventSelected');
        target.addClass('eventSelected');
    }
    // on débloque les touches le plus tard possible afin de passer derrière l'appel ajax
}

function openTargetEvent() {
    window.open($('.eventSelected .articleTitle a').attr('href'), '_blank');
}

function readTargetEvent() {
    var buttonElement = $('.eventSelected .readUnreadButton');
    var id = $(target).attr('id');
    readThis(buttonElement, id, null, function () {
        // on fait un focus sur l'Event suivant
        targetThisEvent($('.eventSelected').nextAll(':visible').first(), true);
        $(window).scroll();
    });
}

function targetPreviousEventRead() {
    targetThisEvent($('.eventSelected').prev().css('display', 'block'), true);
    var buttonElement = $('.eventSelected .readUnreadButton');
    var id = $(target).attr('id');
    unReadThis(buttonElement, id, null);
}

function readAllDisplayedEvents() {
    $('article section').each(function (i, article) {
        var buttonElement = $('.readUnreadButton', article);
        var id = $('.anchor', article).attr('id');
        readThis(buttonElement, id);
    });
}

function switchFavoriteTargetEvent() {
    $('.favorite', target).click();
}

function togglecategory(element, category) {
    fluxBloc = $('ul', $(element).parent().parent());

    open = 0;
    if (fluxBloc.css('display') == 'none') open = 1;
    fluxBloc.slideToggle(200);
    $(element).html(!open ? '<i class="icon-category-empty"></i>' : '<i class="icon-category-open-empty"></i>');
    $.ajax({
        url: "./action.php?action=changecategoryState",
        data: {id: category, isopen: open}
    });
}

function addFavorite(element, id) {
    var activeScreen = $('#pageTop').html();
    // Colorise l'élément pour indiquer la bonne réception de la demande
    $(element).css('color', 'black');
    $.ajax({
        url: "/action/add/favorite",
        data: {id: id},
        success: function (msg) {
            if (msg.status == 'noconnect') {
                alert(msg.texte)
            } else {
                if (console && console.log && msg != "") console.log(msg);
                $(element).attr('onclick', 'removeFavorite(this,' + id + ');').html(_t('UNFAVORIZE'));
                // on compte combien d'article ont été remis en favoris sur la pages favoris (scroll infini)
                if (activeScreen == 'favorites') {
                    $(window).data('nblus', $(window).data('nblus') - 1);
                    addOrRemoveFluxNumber('+');
                }
            }
            $(element).css('color', ''); // Retour au style de classe
        }
    });
}

function removeFavorite(element, id) {
    var activeScreen = $('#pageTop').html();
    // Colorise l'élément pour indiquer la bonne réception de la demande
    $(element).css('color', 'black');
    $.ajax({
        url: "./action.php?action=removeFavorite",
        data: {id: id},
        success: function (msg) {
            if (msg.status == 'noconnect') {
                alert(msg.texte)
            } else {
                if (console && console.log && msg != "") console.log(msg);
                $(element).attr('onclick', 'addFavorite(this,' + id + ');').html(_t('FAVORIZE'));
                // on compte combien d'article ont été remis en favoris sur la pages favoris (scroll infini)
                if (activeScreen == 'favorites') {
                    $(window).data('nblus', $(window).data('nblus') + 1);
                    addOrRemoveFluxNumber('-');
                }
            }
            $(element).css('color', ''); // Retour au style de classe
        }
    });
}

function renamecategory(element, category) {
    var categoryLine = $(element).parent();
    var categoryNameCase = $('span', categoryLine);
    var value = categoryNameCase.html();
    $(element).html('Enregistrer');
    $(element).attr('style', 'background-color:#0C87C9;');
    $(element).attr('onclick', 'saveRenamecategory(this,' + category + ')');
    categoryNameCase.replaceWith('<span><input type="text" name="categoryName" value="' + value + '"/></span>');
}


function saveRenamecategory(element, category) {
    var categoryLine = $(element).parent();
    var categoryNameCase = $('span', categoryLine);
    var value = $('input', categoryNameCase).val();
    $(element).html('Rename');
    $(element).attr('style', 'background-color:#F16529;');
    $(element).attr('onclick', 'renamecategory(this,' + category + ')');
    categoryNameCase.replaceWith('<span>' + value + '</span>');
    $.ajax({
        url: "/settings/category/rename",
        type: 'post',
        data: {id: category, name: value}
    });
}


function renameFlux(element, flux) {
    var fluxLine = $(element).parent().parent();
    var fluxNameCase = fluxLine.children('.js-fluxTitle').children('a:nth-child(1)');
    var fluxNameValue = fluxNameCase.html();
    var fluxUrlCase = fluxLine.children('.js-fluxTitle').children('a:nth-child(2)');
    var fluxUrlValue = fluxUrlCase.attr('href');
    var url = fluxNameCase.attr('href');
    $(element).html('Save');
    $(element).attr('style', 'background-color:#0C87C9;');
    $(element).attr('onclick', 'saveRenameFlux(this,' + flux + ',"' + url + '")');
    fluxNameCase.replaceWith('<input type="text" name="fluxName" value="' + fluxNameValue + '" size="30" />');
    fluxUrlCase.replaceWith('<input type="text" name="fluxUrl" value="' + fluxUrlValue + '" size="30" />');
}

function saveRenameFlux(element, flux, url) {
    var fluxLine = $(element).parent().parent();
    var fluxNameCase = fluxLine.children('.js-fluxTitle:first').children('input[name="fluxName"]');
    var fluxNameValue = fluxNameCase.val();
    var fluxUrlCase = fluxLine.children('.js-fluxTitle:first').children('input[name="fluxUrl"]');
    var fluxUrlValue = fluxUrlCase.val();
    $(element).html('Renommer');
    $(element).attr('style', 'background-color:#F16529;');
    $(element).attr('onclick', 'renameFlux(this,' + flux + ')');
    fluxNameCase.replaceWith('<a href="' + url + '">' + fluxNameValue + '</a>');
    fluxUrlCase.replaceWith('<a class="underlink" href="' + fluxUrlValue + '">' + fluxUrlValue + '</a>');
    $.ajax({
        url: "/settings/flux/rename",
        type: 'post',
        data: {id: flux, name: fluxNameValue, url: fluxUrlValue}
    });
}

// @todo
function changeFluxCategory(element, id) {
    var value = $(element).val();
    window.location = "./action.php?action=changeFluxcategory&flux=" + id + "&category=" + value;
}


function readThis(element, id, from, callback) {
    var activeScreen = $('#pageTop').html();
    var parent = $(element).closest('section');
    var nextEvent = $('#' + id).nextAll(':visible').first();
    //sur les éléments non lus
    if (!parent.hasClass('eventRead')) {
        parent.addClass('eventRead');
        addOrRemoveFluxNumber('-');
        if (console && console.log ) console.log("/action/read/" + activeScreen + "/" + id);
        $.ajax({
            url: "/action/read/" + activeScreen + "/" + id,
            success: function (msg) {
                if (msg.status == 'noconnect') {
                    alert(msg.texte)
                } else {
                    if (console && console.log && msg != "") console.log(msg);
                    switch (activeScreen) {
                        case 'all':
                            // cas de la page d'accueil
                            parent.fadeOut(200, function () {
                                if (callback) {
                                    callback();
                                } else {
                                    targetThisEvent(nextEvent, true);
                                }
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
                            if (callback) {
                                callback();
                            } else {
                                targetThisEvent(nextEvent, true);
                            }
                            // on compte combien d'article ont été lus afin de les soustraires de la requête pour le scroll infini
                            $(window).data('nblus', $(window).data('nblus') + 1);
                            break;
                        default:
                            // autres cas : favoris, selectedFlux ...
                            if (callback) {
                                callback();
                            } else {
                                targetThisEvent(nextEvent, true);
                            }
                            break;
                    }
                }
            }
        });
    } else {  // sur les éléments lus
        // si ce n'est pas un clic sur le titre de l'event
        if (from != 'title') {
            addOrRemoveFluxNumber('+');
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
                        if (callback) {
                            callback();
                        }
                    }
                }
            });
        }
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

//synchronisation manuelle lancée depuis le boutton du menu
function synchronize(code) {
    if (code != '') {
        $('article').prepend('<section>' +
            '<iframe class="importFrame" src="action.php?action=synchronize&format=html&code=' + code + '" name="idFrameSynchro" id="idFrameSynchro" width="100%" height="300" ></iframe>' +
            '</section>');
    } else {
        alert(_t('YOU_MUST_BE_CONNECTED_FEED'));
    }
}


// Disparition block et affichage block clique
function toggleBlocks(target) {
    target = target.substring(1);
    $('#main article > section').not('.logs').hide();
    $('.' + target).fadeToggle(200);
}

// affiche ou cache les fluxs n'ayant pas d'article non lus.
function toggleUnreadFluxcategory(button, action) {
    $.ajax({
        url: "./action.php?action=displayOnlyUnreadFluxcategory&displayOnlyUnreadFluxcategory=" + action,
        success: function (msg) {
            if (msg.status == 'noconnect') {
                alert(msg.texte)
            } else {
                if (console && console.log && msg != "") console.log(msg);
                //Afficher ou cacher les fluxs
                if (action) {
                    $('.hideflux').hide();
                    $(button).find('i').addClass('icon-resize-small').removeClass('icon-resize-full');
                } else {
                    $('.hideflux').show();
                    $(button).find('i').addClass('icon-resize-full').removeClass('icon-resize-small');


                }
                //changement de l'évènement onclick pour faire l'inverse lors du prochain clic
                $(button).attr('onclick', 'toggleUnreadFluxcategory(this,' + !action + ');');

            }
        }
    });
}

function buttonAction(target, id) {
    // Check unreadEvent
    if ($('#pageTop').html()) {
        var from = true;
    } else {
        var from = '';
    }
    readThis(target, id, from);
}


// permet de récupérer les variables passée en get dans l'URL et des les parser
function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        if (hash[1]) {
            rehash = hash[1].split('#');
            vars[hash[0]] = rehash[0];
        } else {
            vars[hash[0]] = '';
        }


    }
    return vars;
}

// affiche ou cache les fluxs n'ayant pas d'article non lus.
function toggleFluxVerbose(button, action, idFlux) {
    $.ajax({
        url: "./action.php?action=displayFluxIsVerbose&displayFluxIsVerbose=" + action + "&idFlux=" + idFlux,
        success: function (msg) {
            if (msg.status == 'noconnect') {
                alert(msg.texte)
            } else {
                if (console && console.log && msg != "") console.log(msg);
                //changement de l'évènement onclick pour faire l'inverse lors du prochain clic
                var reverseaction = 0
                if (action == 0) {
                    reverseaction = 1
                }
                $(button).attr('onclick', 'toggleFluxVerbose(this,' + reverseaction + ', ' + idFlux + ');');
            }
        }
    });
}

// Bouton permettant l'affichage des options d'affichage et de non affichage des flux souhaités en page d'accueil
function toggleOptionFluxVerbose(button, action) {
    $.ajax({
        url: "./action.php?action=optionFluxIsVerbose&optionFluxIsVerbose=" + action,
        success: function (msg) {
            if (msg.status == 'noconnect') {
                alert(msg.texte)
            } else {
                if (console && console.log && msg != "") console.log(msg);
                //changement de l'évènement onclick pour faire l'inverse lors du prochain clic
                var reverseaction = 0
                if (action == 0) {
                    reverseaction = 1
                }
                $(button).attr('onclick', 'toggleOptionFluxVerbose(this,' + reverseaction + ');');
                //Changement du statut des cases à cocher sur les flux (afficher ou cacher)
                if (action == 1) {
                    $('.fluxVerbose').hide();
                } else {
                    $('.fluxVerbose').show();
                }
            }
        }
    });
}

// fonction d'ajout ou de retrait d'un article dans les compteurs
// operator = '-' pour les soustraction '+' pour les ajouts
function addOrRemoveFluxNumber(operator) {
    if (operator == '-') {
        // on diminue le nombre d'article en haut de page
        var nb = parseInt($('#nbarticle').html()) - 1;
        if (nb > 0) {
            $('#nbarticle').html(nb);
        } else {
            $('#nbarticle').html(0);
        }
        // on diminue le nombre sur le flux en question
        var flux_id = ($('.selectedFlux').eq(0).data('id'));
        if (console && console.log ) console.log("Flux id: " + flux_id);
        var flux = $('#menuBar ul a[data-id="' + flux_id + '"]').next().find('span');
        if (console && console.log ) console.log("Flux text: " + $(flux).text());
        nb = parseInt($(flux).text()) - 1;
        if (nb > 0) {
            $(flux).text(nb);
        } else {
            $(flux).text(0);
        }
        // on diminue le nombre sur le dossier
        var flux_category = ($(flux).closest('ul').prev('h1').find('.unreadForcategory'));
        if (isNaN(flux_category.html())) {
            var regex = '[0-9]+';
            var found = flux_category.html().match(regex);
            nb = parseInt(found[0]) - 1;
            var regex2 = '[^0-9]+';
            var lib = flux_category.html().match(regex2);
            if (nb > 0) {
                flux_category.html(nb + lib[0])
            } else {
                flux_category.html('0' + lib[0])
            }
        }
    } else {
        // on augmente le nombre d'article en haut de page
        var nb = parseInt($('#nbarticle').html()) + 1;
        $('#nbarticle').html(nb);
        // on augmente le nombre sur le flux en question
        var flux_id = ($('.eventSelected').eq(0).data('flux'));
        var flux = $('#menuBar ul a[href$="flux=' + flux_id + '"]').next().find('span');
        nb = parseInt($(flux).text()) + 1;
        $(flux).text(nb);
        // on augmente le nombre sur le dossier
        var flux_category = ($(flux).closest('ul').prev('h1').find('.unreadForcategory'));
        if (isNaN(flux_category.html())) {
            var regex = '[0-9]+';
            var found = flux_category.html().match(regex);
            nb = parseInt(found[0]) + 1;
            var regex2 = '[^0-9]+';
            var lib = flux_category.html().match(regex2);
            if (nb > 0) {
                flux_category.html(nb + lib[0])
            } else {
                flux_category.html('0' + lib[0])
            }
        }
    }
}

function isIntoView(elem) {
    var windowEl = $(window);
    // ( windowScrollPosition + windowHeight ) > last entry top position
    return (windowEl.scrollTop() + windowEl.height()) > $('section:last').offset().top;
}

function getFluxName(id) {
    return $('[data-flux-id=' + id + ']').html();
}

function markAllAsRead(el) {
    var infoLink = {};
    var translation = '';
    var action = '';
    var type = el.data('mark-all-read');
    switch (type) {
        case 'category':
            infoLink = el.siblings('.categoryLink');
            translation = 'READ_ALL_category_CONFIRM';
            action = 'readcategory';
            break;
        case 'flux':
            infoLink = el.siblings('.fluxLink');
            translation = 'CONFIRM_MARK_FEED_AS_READ';
            action = 'read/flux';
            break;
    }
    if (confirm("Mark as Read") + '\n\n' + infoLink.html()) {
        window.location = '/action/' + action + '/' + infoLink.data('id');
    }
}

function randomOtpSecret(inputEl, qrcodeEl) {
    var base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    var secretLength = 16;
    var otpSecret = '';
    for (i = 0; i < secretLength; i++) {
        otpSecret = otpSecret + base32chars[Math.floor(Math.random() * base32chars.length)];
    }
    url = qrcodeEl.attr("src").replace(/key=[a-zA-Z2-7]*/, 'key=' + otpSecret);
    //url = url.replace(/label=[a-zA-Z2-7]*/, 'label='+otpSecret); //DEBUG: ajout du secret dans le label, donc visible !
    qrcodeEl.attr("src", url);
    inputEl.val(otpSecret);
}
