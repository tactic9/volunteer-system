</div>
    <footer class="bg-light py-3 mt-4">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> Volunteer System</p>
        </div>
    </footer>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>