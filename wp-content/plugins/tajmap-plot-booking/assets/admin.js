(function($){
	var points=[]; var drawing=false; var image=null; var ctx=null; var scale=1; var plots=[]; var selectedId=null;
	function initCanvas(){ var canvas=$('#tajmap-pb-canvas')[0]; if(!canvas) return; ctx=canvas.getContext('2d'); drawAll(); }
	function drawAll(){ if(!ctx) return; ctx.clearRect(0,0,ctx.canvas.width,ctx.canvas.height); if(image){ ctx.drawImage(image,0,0,ctx.canvas.width,ctx.canvas.height);} // draw existing plots
		plots.forEach(function(p){ drawPolygonPath(p.coordinates, p.status==='sold'?'rgba(220,53,69,.35)':'rgba(0,128,0,.35)', p.id===selectedId?3:2, p.status==='sold'?'#dc3545':'#1e7e34'); });
		if(points.length>0){ drawPolygonPath(points,'rgba(0,123,255,.2)',2,'#007bff'); }
	}
	function drawPolygonPath(coords, fill, lineW, stroke){ var pts = Array.isArray(coords)?coords:JSON.parse(coords||'[]'); if(pts.length<2) return; ctx.save(); ctx.beginPath(); ctx.moveTo(pts[0].x, pts[0].y); for(var i=1;i<pts.length;i++){ ctx.lineTo(pts[i].x, pts[i].y);} ctx.closePath(); ctx.fillStyle=fill; ctx.strokeStyle=stroke; ctx.lineWidth=lineW; ctx.fill(); ctx.stroke(); ctx.restore(); }
	function loadImageById(id){ if(!id){ image=null; drawAll(); return; } wp.media.attachment(id).fetch().then(function(){ var url=wp.media.attachment(id).get('url'); image=new Image(); image.onload=function(){ var c=$('#tajmap-pb-canvas')[0]; // try fit
			var ratio=image.width/image.height; if(c.width/c.height>ratio){ c.width = Math.round(c.height*ratio);} else { c.height=Math.round(c.width/ratio);} drawAll(); };
		image.src=url; $('#tajmap-pb-image-info').text('Image ID: '+id); }); }
	function refreshList(){ var ul=$('#tajmap-pb-plot-list').empty(); plots.forEach(function(p){ var label=p.plot_name+' - '+(p.status); $('<li/>').text(label).attr('data-id',p.id).css('cursor','pointer').appendTo(ul); }); }
	function fetchPlots(){ $.post(TajMapPB.ajaxUrl,{ action:'tajmap_pb_get_plots', nonce:TajMapPB.nonce }, function(res){ if(res&&res.success){ plots=res.data.plots||[]; // set base image if present on first
			var first = plots.find(function(p){return p.base_image_id}); if(first){ $('#tajmap-pb-base-image-id').val(first.base_image_id); loadImageById(first.base_image_id); }
			refreshList(); drawAll(); }}); }
	$(document).on('click','#tajmap-pb-start-poly',function(e){ e.preventDefault(); drawing=true; points=[]; drawAll(); });
	$(document).on('click','#tajmap-pb-reset-poly',function(e){ e.preventDefault(); drawing=false; points=[]; selectedId=null; drawAll(); });
	$('#tajmap-pb-canvas').on('click',function(e){ if(!drawing) return; var off=$(this).offset(); var x=e.pageX-off.left; var y=e.pageY-off.top; points.push({x:Math.round(x), y:Math.round(y)}); drawAll(); });
	$(document).on('click','#tajmap-pb-complete-poly',function(e){ e.preventDefault(); if(points.length<3){ alert('At least 3 points required'); return;} drawing=false; drawAll(); });
	$(document).on('click','#tajmap-pb-upload-image',function(e){ e.preventDefault(); var frame=wp.media({ title:'Select Base Map', button:{text:'Use this image'}, multiple:false}); frame.on('select',function(){ var attachment=frame.state().get('selection').first().toJSON(); $('#tajmap-pb-base-image-id').val(attachment.id); loadImageById(attachment.id); }); frame.open(); });
	$(document).on('click','#tajmap-pb-save-plot',function(e){ e.preventDefault(); var data={ action:'tajmap_pb_save_plot', nonce:TajMapPB.nonce, id:$('#tajmap-pb-plot-id').val(), plot_name:$('#tajmap-pb-plot-name').val(), street:$('#tajmap-pb-street').val(), sector:$('#tajmap-pb-sector').val(), block:$('#tajmap-pb-block').val(), status:$('#tajmap-pb-status').val(), coordinates:JSON.stringify(points), base_image_id:$('#tajmap-pb-base-image-id').val() }; if((points||[]).length<3 && !data.id){ return alert('Draw polygon first'); } $.post(TajMapPB.ajaxUrl, data, function(res){ if(res&&res.success){ if(!data.id){ data.id=res.data.id; } fetchPlots(); $('#tajmap-pb-plot-id').val(''); points=[]; alert('Saved'); }});
	});
	$(document).on('click','#tajmap-pb-delete-plot',function(e){ e.preventDefault(); var id=$('#tajmap-pb-plot-id').val()||selectedId; if(!id){ return alert('Select a plot'); } if(!confirm('Delete this plot?')) return; $.post(TajMapPB.ajaxUrl,{ action:'tajmap_pb_delete_plot', nonce:TajMapPB.nonce, id:id }, function(res){ if(res&&res.success){ fetchPlots(); points=[]; $('#tajmap-pb-plot-id').val(''); selectedId=null; }});
	});
	$('#tajmap-pb-plot-list').on('click','li',function(){ var id=parseInt($(this).attr('data-id'),10); selectedId=id; var p=plots.find(function(x){return x.id==id}); if(!p) return; $('#tajmap-pb-plot-id').val(p.id); $('#tajmap-pb-plot-name').val(p.plot_name); $('#tajmap-pb-street').val(p.street); $('#tajmap-pb-sector').val(p.sector); $('#tajmap-pb-block').val(p.block); $('#tajmap-pb-status').val(p.status); points = JSON.parse(p.coordinates||'[]'); if(p.base_image_id){ $('#tajmap-pb-base-image-id').val(p.base_image_id); loadImageById(p.base_image_id); } drawAll(); });
	$(function(){ initCanvas(); fetchPlots(); });
})(jQuery);
