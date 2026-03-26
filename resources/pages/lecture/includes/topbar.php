<section class="header">
    <div class="logo">
        <i class="ri-menu-line icon menu"></i>
        <div class="logo-icon-main">AF</div>
        <h2>AFRA<span>s</span></h2>
    </div>
    <div class="search--notification--profile">
        <div id="searchInput" class="search">
            <button onclick="searchItems()"><i class="ri-search-2-line"></i></button>
            <input type="text" id="searchText" placeholder="Search records...">
        </div>
        <div class="notification--profile">
            <div class="picon lock">
                <i class="ri-user-3-line" style="margin-right:6px;"></i><?php echo $logged_in->name ?>
            </div>
            <div class="picon profile">
                <img src="resources/images/user.png" alt="">
            </div>
        </div>
    </div>
</section>

<script>
    function searchItems() {
        var input = document.getElementById('searchText').value.toLowerCase();
        var rows = document.querySelectorAll('table tr');
        rows.forEach(function(row) {
            var cells = row.querySelectorAll('td');
            var found = false;
            cells.forEach(function(cell) {
                if (cell.innerText.toLowerCase().includes(input)) found = true;
            });
            row.style.display = found ? '' : 'none';
        });
    }
</script>
