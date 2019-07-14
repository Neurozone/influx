function search_readUnread(element,id){
	
	
	if(!$(element).hasClass('eventRead')){
		$(element).addClass('eventRead').html(_t('P_SEARCH_BTN_NONLU',null));
		$.ajax({
					  url: "./action.php?action=readContent",
					  data:{id:id},
					  success:function(msg){
					  	if(msg!="") alert('Erreur de lecture : '+msg);
					  }
		});
	}else{
		$(element).removeClass('eventRead').html(_t('P_SEARCH_BTN_LU',null));
				$.ajax({
					url: "./action.php?action=unreadContent",
					data:{id:id}
		});		
	}	
}


function search_favorize(element,id){
	if(!$(element).hasClass('eventFavorite')){
		$(element).addClass('eventFavorite').html(_t('P_SEARCH_BTN_UNFAVORIZE',null));
		$.ajax({
					  url: "./action.php?action=addFavorite",
					  data:{id:id},
					  success:function(msg){
					  	if(msg!="") alert('Erreur de lecture : '+msg);
					  }
		});
	}else{
		$(element).removeClass('eventFavorite').html(_t('P_SEARCH_BTN_FAVORIZE',null));
				$.ajax({
					url: "./action.php?action=removeFavorite",
					data:{id:id}
		});
			
	}
}