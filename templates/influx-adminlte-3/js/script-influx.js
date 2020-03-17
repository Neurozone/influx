$(document).ready(function () {

  // Page settings
  if ($('.settings').length) {

    // Si nom du bloc en hash dans url
    var hash = window.location.hash;
    if (hash.length) {
      toggleBlocks(hash);
    }

    $('[data-otp-generate]').click(function () {
      var otpGeneratorEl = $(this);
      randomOtpSecret($(otpGeneratorEl.data('otp-generate')), $(otpGeneratorEl.data('otp-qrcode')));
    })

  } else {

    targetThisEvent($('article section:first'), true);

    $('[data-mark-all-read]').click(function () {
      markAllAsRead($(this));
    });

    // on initialise ajaxready à true au premier chargement de la fonction
    $(window).data('page', 1);
    $(window).data('nblus', 0);

    if ($(window).scrollTop() == 0) scrollInfini();
  }

  $('[data-toggle-group]').click(function () {
    toggleTab($(this));
  });

});

$(window).scroll(function () {
  scrollInfini();
});

function scrollInfini() {

  var win = $(window);
  // End of the document reached?
  if ($(document).height() - win.height() == win.scrollTop()) {
    var url = window.location.toString();
    var url_split = url.split('/');

    if (console && console.log) console.log(url_split.length);

    if (url_split.length > 4) {
      var id = url_split[4].split('#');
      var flux = id[0];
    } else {
      var flux = null;
    }

    var url_route = {"protocol": url_split[0], "root": url_split[2], "action": url_split[3]}


    if (console && console.log) console.log(url_route);
    if (page == null) var page = 0;

    $.ajax({
      url: '/item/select',
      type: 'post',
      data: {flux: flux, page: $(window).data('page')},

      success: function (data) {
        $('.row').append(data).last();
      }

    });
    $(window).data('page', $(window).data('page') + 1);
  }


};

/* Fonctions de sélections */

/*
function targetPreviousEvent() {
  targetThisEvent($('.eventSelected').prevAll(':visible').first(), true);
}

function targetNextEvent() {

  targetThisEvent($('.eventSelected').nextAll(':visible').first(), true);
}
*/

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
    var id = target.attr('id');
    if (id && focusOn) window.location = '#' + id;
  }
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


/*
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
*/

/*
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
*/

/*
function renameCategory(element, category) {
  var categoryLine = $(element).parent();
  var categoryNameCase = $('span', categoryLine);
  var value = categoryNameCase.html();
  $(element).html('Enregistrer');
  $(element).attr('style', 'background-color:#0C87C9;');
  $(element).attr('onclick', 'saveRenameCategory(this,' + category + ')');
  categoryNameCase.replaceWith('<input class="form-control" placeholder="" type="text" name="categoryName" value="' + value + '"/>');
}


function saveRenameCategory(element, category) {
  var categoryLine = $(element).parent();
  var categoryNameCase = $('span', categoryLine);
  var value = $('input', categoryNameCase).val();
  $(element).html('Rename');
  $(element).attr('style', 'background-color:#F16529;');
  $(element).attr('onclick', 'renameCategory(this,' + category + ')');
  categoryNameCase.replaceWith('<span>' + value + '</span>');
  $.ajax({
    url: "/settings/category/rename",
    type: 'post',
    data: {id: category, name: value}
  });
}
*/

$(document).find('.btn_save').hide();
$(document).find('.btn_cancel').hide();

$(document).on('click', '.row_data', function (event) {
  event.preventDefault();

  if ($(this).attr('edit_type') == 'button') {
    return false;
  }

  //make div editable
  $(this).closest('div').attr('contenteditable', 'true');
  //add bg css
  $(this).addClass('bg-warning').css('padding', '5px');

  $(this).focus();
})
//--->make div editable > end

//--->save single field data > start
$(document).on('focusout', '.row_data', function (event) {
  event.preventDefault();

  if ($(this).attr('edit_type') == 'button') {
    return false;
  }

  var row_id = $(this).closest('tr').attr('id');

  var row_div = $(this)
    .removeClass('bg-warning') //add bg css
    .css('padding', '')

  var col_name = row_div.attr('col_name');
  var col_val = row_div.html();

  var arr = {};
  arr[col_name] = col_val;

  //use the "arr"	object for your ajax call
  $.extend(arr, {row_id: row_id});

  console.debug(arr);
  console.debug(row_id);

  window.alert(arr.name);

  $.ajax({
    url: "/settings/flux/rename",
    type: 'post',
    data: {id: row_id, name: fluxNameValue, url: fluxUrlValue}
  });

  //out put to show
  $('.post_msg').html('<pre class="bg-success">' + JSON.stringify(arr, null, 2) + '</pre>');

})
//--->save single field data > end

//--->button > edit > start
$(document).on('click', '.btn_edit', function (event) {
  event.preventDefault();
  var tbl_row = $(this).closest('tr');

  var row_id = tbl_row.attr('row_id');

  tbl_row.find('.btn_save').show();
  tbl_row.find('.btn_cancel').show();

  //hide edit button
  tbl_row.find('.btn_edit').hide();

  //make the whole row editable
  tbl_row.find('.row_data')
    .attr('contenteditable', 'true')
    .attr('edit_type', 'button')
    .addClass('bg-warning')
    .css('padding', '3px')

  //--->add the original entry > start
  tbl_row.find('.row_data').each(function (index, val) {
    //this will help in case user decided to click on cancel button
    $(this).attr('original_entry', $(this).html());
  });
  //--->add the original entry > end

});
//--->button > edit > end

//--->button > cancel > start
$(document).on('click', '.btn_cancel', function (event) {
  event.preventDefault();

  var tbl_row = $(this).closest('tr');

  var row_id = tbl_row.attr('id');

  //hide save and cancel buttons
  tbl_row.find('.btn_save').hide();
  tbl_row.find('.btn_cancel').hide();

  //show edit button
  tbl_row.find('.btn_edit').show();

  //make the whole row editable
  tbl_row.find('.row_data')
    .attr('edit_type', 'click')
    .removeClass('bg-warning')
    .css('padding', '')

  tbl_row.find('.row_data').each(function (index, val) {
    $(this).html($(this).attr('original_entry'));
  });
});
//--->button > cancel > end


//--->save whole row entery > start
$(document).on('click', '.btn_save', function (event) {
  event.preventDefault();
  var tbl_row = $(this).closest('tr');

  var row_id = tbl_row.attr('id');


  //hide save and cacel buttons
  tbl_row.find('.btn_save').hide();
  tbl_row.find('.btn_cancel').hide();

  //show edit button
  tbl_row.find('.btn_edit').show();


  //make the whole row editable
  tbl_row.find('.row_data')
    .attr('edit_type', 'click')
    .removeClass('bg-warning')
    .css('padding', '')

  //--->get row data > start
  var arr = {};
  tbl_row.find('.row_data').each(function (index, val) {
    var col_name = $(this).attr('col_name');
    if (col_name != 'url') {
      var col_val = $(this).html();
    } else {
      var col_val = $(this).children().html();
    }

    arr[col_name] = col_val;
  });

  tbl_row.find('.row_select').each(function (index, val) {
    var select_name = $(this).attr('col_name');
    var col_select_val = $(this).children().val();
    arr[select_name] = col_select_val
  });

  //--->get row data > end

  //use the "arr"	object for your ajax call
  $.extend(arr, {row_id: row_id});

  $.ajax({
    url: "/settings/flux/rename",
    type: 'post',
    data: {id: arr.row_id, name: arr.name, url: arr.url}
  });

  //out put to show
  $('.post_msg').html('<pre class="bg-success">' + JSON.stringify(arr, null, 2) + '</pre>')


});
//--->save whole row entery > end

// @todo
function changeFluxCategory(element, id) {
  var value = $(element).val();
  window.location = "./action.php?action=changeFluxcategory&flux=" + id + "&category=" + value;
}

function markReadUnread(type, element, id) {
  /*
  Can be category / flux / item
   */
  if (type == 'category' || type == 'flux') {
    $.ajax({
      url: "/" + type + "/update/read",
      type: 'post',
      data: {id: id},
      success: function (msg) {
        if (msg.status == 'noconnect') {
          alert(msg.text)
        } else {
          if (console && console.log && msg != "") console.log(msg.text);
        }
      }
    });
  } else {
    var row = $('#' + id)
    $.ajax({
      url: "/item/update/readUnread",
      type: 'post',
      data: {guid: id},
      success: function (msg) {
        if (msg.status == 'noconnect') {
          alert(msg.text)
        } else {
          if (console && console.log && msg != "") console.log(msg.text);
          parent.removeClass('eventRead');
        }
      }
    });
  }
  addOrRemoveFluxNumber('-');

}

function markFlaggedUnflagged(element, id) {
    $.ajax({
      url: "/item/update/flaggedUnflagged",
      type: 'post',
      data: {guid: id},
      success: function (msg) {
        if (msg.status == 'noconnect') {
          alert(msg.text)
        } else {
          if (console && console.log && msg != "") console.log(msg.text);
        }
      }
    });
}

// @TODO refaire cette fonction
function readThis(element, id, from, callback) {
  var activeScreen = $('#pageTop').html();
  var parent = $(element).closest('rows');
  if (console && console.log) console.log("nextevent");
  var nextEvent = $('#' + id).nextAll(':visible').first();
  console.debug(nextEvent);
  //sur les éléments non lus
  if (!parent.hasClass('eventRead')) {
    parent.addClass('eventRead');
    addOrRemoveFluxNumber('-');
    if (console && console.log) console.log("/action/read/" + activeScreen + "/" + id);
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

// @TODO refaire cette fonction
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

/*
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
*/

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
    if (console && console.log) console.log("Flux id: " + flux_id);
    var flux = $('.unreadForFeed').filter('[data-flux-id="' + flux_id + '"]');
    if (console && console.log) console.log("Flux text: " + $(flux).text());
    console.debug(flux);
    //if (console && console.log ) console.log("Flux text: " + $('.unreadForFeed').filter('[data-flux-id="' + flux_id + '"]').text());
    nb = parseInt($(flux).text()) - 1;
    if (nb > 0) {
      $(flux).text(nb);
    } else {
      $(flux).text(0);
    }
    // on diminue le nombre sur le dossier
    /*
    var flux_category = ($(flux).parent('.unreadForFolder'));
    console.debug(flux_category);
    if (console && console.log) console.log("flux_category: " + flux_category.text());
    if (isNaN(flux_category.html())) {
      var regex = '[0-9]+';
      if (console && console.log) console.log(flux_category.html().match(regex));
      if (console && console.log) console.log(flux_category.html());
      var found = flux_category.html().match(regex);
      nb = parseInt(found[0]) - 1;
      var regex2 = '[^0-9]+';
      var lib = flux_category.html().match(regex2);
      if (nb > 0) {
        flux_category.html(nb + lib[0])
      } else {
        flux_category.html('0' + lib[0])
      }
    } */
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

/*
function isIntoView(elem) {
  var windowEl = $(window);
  // ( windowScrollPosition + windowHeight ) > last entry top position
  return (windowEl.scrollTop() + windowEl.height()) > $('section:last').offset().top;
}
*/

function getFluxName(id) {
  return $('[data-flux-id=' + id + ']').html();
}

/*
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
*/


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
