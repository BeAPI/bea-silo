<script type="text/html" id='results-tpl'>
	<% _.each(data.posts, function(val,key,list){ %>
		<div class="item__result tiles__col--4 <% _.each(val.terms_slugs, function(val,key,list){ %><%= val %> <% }) %>">
			<h3 class="item__title">
				<%= val.post_title %>
			</h3>
			<div class="item__content">
				<%= val.post_excerpt %>
			</div>
			<a href="<%= val.permalink %>" class="item__link">
				<%= bea_silo.read_more_label %>
			</a>
		</div>
	<% }) %>
</script>