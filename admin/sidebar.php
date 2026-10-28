<div class="bg-blue-800 text-white w-64">
    <div class="p-4">
        <h1 class="text-2xl font-bold">MediBook Admin</h1>
        <p class="text-blue-200 text-sm">Welcome, <?php echo $_SESSION['first_name']; ?></p>
    </div>
    <nav class="mt-6">
        <a href="index.php" class="block py-2 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
        </a>
        <a href="appointments.php" class="block py-2 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
            <i class="fas fa-calendar-alt mr-2"></i>Appointments
        </a>
        <a href="users.php" class="block py-2 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
            <i class="fas fa-users mr-2"></i>Users
        </a>
        <a href="doctors.php" class="block py-2 px-4 <?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'bg-blue-900' : 'hover:bg-blue-700'; ?>">
            <i class="fas fa-user-md mr-2"></i>Doctors
        </a>
        <a href="logout.php" class="block py-2 px-4 hover:bg-blue-700 mt-4">
            <i class="fas fa-sign-out-alt mr-2"></i>Logout
        </a>
    </nav>
</div>