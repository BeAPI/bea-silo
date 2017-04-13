<!-- Silo _s button -->
<script type="text/html" id='button-tpl'>
    <% _.each(filteredData, function(val,key,list){ %>
        <li class="bubble button" data-id="<%= val.id %>" data-term-link="<%= val.term_link %>">
            <button class="button button--round button__light" data-theme="<%= val.thematic_name %>" data-background="<%= val.background_image_srcset %>" data-foreground="<%= val.foreground_image_srcset %>">
                <span><%= val.name %></span>
            </button>
        </li>
        <% }) %>
</script>