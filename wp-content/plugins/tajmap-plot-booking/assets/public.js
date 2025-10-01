(function($){
	var plots=[]; var $img=null; var $svg=null; var $tooltip=null; var selectedPlotId=null;
	function fetchPlots(){ return $.post(TajMapPBPublic.ajaxUrl,{ action:'tajmap_pb_get_plots', nonce:TajMapPBPublic.nonce }); }
	function setBaseImage(){ var first=plots.find(function(p){return p.base_image_url}); if(!first) return; $img.attr('src', first.base_image_url); }
	function renderSVG(){ $svg.empty(); plots.forEach(function(p){ var pts = []; try{ pts = JSON.parse(p.coordinates||'[]'); }catch(e){}
			if(!pts.length) return; var pointsAttr = pts.map(function(pt){ return pt.x+","+pt.y; }).join(' ');
			var cls = p.status==='sold'?'tajmap-pb-plot-sold':'tajmap-pb-plot-available';
			var labelParts = [];
			if(p.plot_name){ labelParts.push(p.plot_name); }
			if(p.street){ labelParts.push(p.street); }
			var label = labelParts.join(' ');
			if(p.block){ label += ', '+p.block+' Block'; }
			var $poly = $(document.createElementNS('http://www.w3.org/2000/svg','polygon'))
				.attr('points', pointsAttr)
				.attr('data-id', p.id)
				.attr('data-label', label)
				.addClass(cls);
			$svg.append($poly);
		}); }
	function bindEvents(){ $svg.on('mouseenter','polygon',function(){ var label=$(this).attr('data-label'); $(this).addClass('tajmap-pb-plot-hover'); $tooltip.text(label).show(); }).on('mouseleave','polygon',function(){ $(this).removeClass('tajmap-pb-plot-hover'); $tooltip.hide(); }).on('mousemove','polygon',function(e){ $tooltip.css({left:e.pageX+12, top:e.pageY+12}); }).on('click','polygon',function(){ var id=parseInt($(this).attr('data-id'),10); var p=plots.find(function(x){return x.id==id}); if(!p) return; if(p.status==='sold'){ return; } selectedPlotId=id; $('#tajmap-pb-selected-plot-id').val(id); openModal(); });
		$('.tajmap-pb-close').on('click',closeModal); $(document).on('keydown',function(e){ if(e.key==='Escape'){ closeModal(); }});
		$('#tajmap-pb-lead-form').on('submit',function(e){ e.preventDefault(); var phone=$('#tajmap-pb-phone').val().trim(); var email=$('#tajmap-pb-email').val().trim(); if(!/^[- +()0-9]{7,20}$/.test(phone)){ return toast('Enter a valid phone'); } if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ return toast('Enter a valid email'); }
			var data=$(this).serializeArray().reduce(function(acc,it){ acc[it.name]=it.value; return acc; },{}); data.action='tajmap_pb_save_lead'; data.nonce=TajMapPBPublic.nonce; $.post(TajMapPBPublic.ajaxUrl,data,function(res){ if(res&&res.success){ toast('Submitted. We will contact you.'); closeModal(); } else { toast((res&&res.data&&res.data.message)||'Failed'); } });
		}); }
	function toast(msg){ var $f=$('#tajmap-pb-form-feedback'); $f.text(msg).show(); setTimeout(function(){ $f.fadeOut(); },2000); }
	function openModal(){ $('#tajmap-pb-modal').show(); }
	function closeModal(){ $('#tajmap-pb-modal').hide(); }
	$(function(){ $img=$('#tajmap-pb-base-image'); $svg=$('#tajmap-pb-svg'); $tooltip=$('#tajmap-pb-tooltip'); fetchPlots().then(function(res){ if(res&&res.success){ plots=res.data.plots||[]; setBaseImage(); renderSVG(); bindEvents(); } }); });
})(jQuery);
