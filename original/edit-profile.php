<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$uid = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Empty fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address)) {

        $message = "Please fill in all required fields.";
        $messageType = "error";

    }

    // Invalid email
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";
        $messageType = "error";

    }

    // Phone number format
    elseif (!preg_match('/^\d{11}$/', $phone)) {

        $message = "Please enter a valid 11-digit phone number using numbers only.";
        $messageType = "error";

    }

    else {

        try {

            // Make sure no OTHER account already uses this email
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $uid]);

            if ($check->rowCount() > 0) {

                $message = "That email address is already in use by another account.";
                $messageType = "error";

            } else {

                $update = $conn->prepare("
                    UPDATE users
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ?
                    WHERE id = ?
                ");

                $success = $update->execute([$first_name, $last_name, $email, $phone, $address, $uid]);

                if ($success) {

                    // Keep the session in sync so the new info shows up
                    // immediately across every page (header, profile, etc.)
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['last_name'] = $last_name;
                    $_SESSION['email'] = $email;

                    header("Location: my-profile.php?profile_updated=1");
                    exit();

                } else {

                    $message = "Something went wrong updating your profile. Please try again.";
                    $messageType = "error";

                }

            }

        } catch (PDOException $e) {

            $message = "Database Error: " . $e->getMessage();
            $messageType = "error";

        }

    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Profile | Luntiang H.A.P.A.G.</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700&family=Nunito:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Nunito', sans-serif; }
    .font-black { font-family: 'Nunito', serif; }
  </style>
</head>
<body class="bg-[#f4faf5] text-[#1a2e1c] min-h-screen flex flex-col">

  <!-- Header -->
  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- Edit Profile Form -->
  <main class="flex-1 flex items-center justify-center px-6 py-16">
    <div class="w-full max-w-2xl">

        <a href="my-profile.php" class="inline-flex items-center gap-2 text-sm text-[#17611f] hover:text-[#14521a] transition-colors mb-6">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back to My Profile
        </a>
    <div class="w-full max-w-2xl bg-white rounded-3xl shadow-sm border border-gray-100 p-9">
      <span class="inline-block text-[11px] font-semibold tracking-wide text-[#5a7a5c] bg-gray-100 rounded-full px-3 py-1 mb-5">EDIT PROFILE</span>
      <h1 class="font-black text-3xl font-semibold text-[#1a2e1c] mb-2">Update your information</h1>
      <p class="text-[#5a7a5c] text-sm mb-8">Keep your name and email address up to date.</p>

      <?php if (!empty($message)): ?>

<div id="alertMessage"
     class="mb-6 flex items-start gap-3 rounded-2xl border px-5 py-4 shadow-sm transition-all duration-500
     <?= $messageType == 'error'
        ? 'bg-red-50 border-red-200 text-red-700'
        : 'bg-green-50 border-green-200 text-green-700'; ?>">

    <!-- Icon -->
    <div class="mt-0.5">

        <?php if($messageType == 'error'): ?>

        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-6 h-6 text-red-500"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
        </svg>

        <?php else: ?>

        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-6 h-6 text-green-500"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor"
             stroke-width="2">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12l2 2 4-4m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>

        <?php endif; ?>

    </div>

    <!-- Message -->
    <div class="flex-1">

        <h3 class="font-semibold">

            <?= $messageType == 'error'
                ? 'Update Failed'
                : 'Success'; ?>

        </h3>

        <p class="text-sm mt-1">

            <?= htmlspecialchars($message); ?>

        </p>

    </div>

    <!-- Close Button -->
    <button type="button"
            onclick="closeAlert()"
            class="text-[#9e9e9e] hover:text-[#1a2e1c] transition">

        ✕

    </button>

</div>

<script>

function closeAlert(){

    const alert = document.getElementById("alertMessage");

    if(alert){

        alert.classList.add("opacity-0","translate-y-2");

        setTimeout(()=>{

            alert.remove();

        },400);

    }

}

setTimeout(closeAlert,5000);

</script>

<?php endif; ?>

      <form class="space-y-5" method="POST">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          <div>
            <label for="first_name" class="block text-sm font-medium text-[#1a2e1c] mb-2">First Name</label>
            <input type="text" id="first_name" name="first_name" placeholder="First Name" required
                   value="<?= htmlspecialchars($_POST['first_name'] ?? $user['first_name']) ?>"
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          </div>
          <div>
            <label for="last_name" class="block text-sm font-medium text-[#1a2e1c] mb-2">Last Name</label>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name" required
                   value="<?= htmlspecialchars($_POST['last_name'] ?? $user['last_name']) ?>"
                   class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
          </div>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-[#1a2e1c] mb-2">Email Address</label>
          <input type="email" id="email" name="email" placeholder="your@email.com" required
                 value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>

        <div>
          <label for="phone" class="block text-sm font-medium text-[#1a2e1c] mb-2">Phone Number</label>
          <input type="text" id="phone" name="phone" placeholder="09123456789" required minlength="11" maxlength="11" inputmode="numeric" pattern="[0-9]*"
                 value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone']) ?>"
                 oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                 class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors" />
        </div>

        <div>
          <label for="address" class="block text-sm font-medium text-[#1a2e1c] mb-2">Address</label>
          <textarea id="address" name="address" rows="3" placeholder="Street, City, State, ZIP Code" required
                    class="w-full rounded-xl border border-[rgba(27,94,32,0.12)] px-4 py-3 text-sm text-[#1a2e1c] placeholder-[#9e9e9e] focus:outline-none focus:ring-2 focus:ring-[#52b788]/40 focus:border-[#52b788] transition-colors resize-y"><?= htmlspecialchars($_POST['address'] ?? $user['address'] ?? '') ?></textarea>
        </div>

        <div class="flex flex-wrap gap-4 pt-1">
          <button type="submit" class="px-6 py-3 rounded-full bg-[#17611f] text-white text-sm font-medium hover:bg-[#14521a] transition-colors">Save Changes</button>
          <a href="my-profile.php" class="px-6 py-3 rounded-full border border-gray-300 text-[#1a2e1c] text-sm font-medium hover:bg-gray-100 transition-colors">Cancel</a>
        </div>
      </form>
    </div>
    </div>
  </main>

  <!-- Footer -->
  <?php include __DIR__ . '/includes/footer.php'; ?>

</body>
</html>