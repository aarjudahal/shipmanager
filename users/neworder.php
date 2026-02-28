<?php
session_start();
require '../auth/connection.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$successMsg = $_SESSION['successMsg'] ?? "";
$errorMsg   = $_SESSION['errorMsg'] ?? "";
unset($_SESSION['successMsg'], $_SESSION['errorMsg']);

$errors = $_SESSION['form_errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['old']);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $_SESSION['user_id'];
    $sender_name = trim($_POST['sender_name'] ?? '');
    $sender_phone = trim($_POST['sender_phone'] ?? '');
    $sender_email = trim($_POST['sender_email'] ?? '');
    $pickup_address = trim($_POST['pickup_address'] ?? '');

    $receiver_name = trim($_POST['receiver_name'] ?? '');
    $receiver_phone = trim($_POST['receiver_phone'] ?? '');
    $receiver_email = trim($_POST['receiver_email'] ?? '');
    $delivery_address = trim($_POST['delivery_address'] ?? '');

    $package_type = $_POST['package_type'] ?? '';
    $instructions = trim($_POST['instructions'] ?? '');

    $validationErrors = [];

    // --- SENDER VALIDATION ---
    if ($sender_name === '') {
        $validationErrors['sender_name'] = "Sender name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $sender_name)) {
        $validationErrors['sender_name'] = "Numbers/symbols not allowed in names.";
    }

    if ($sender_phone === '') {
        $validationErrors['sender_phone'] = "Sender phone is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $sender_phone)) {
        $validationErrors['sender_phone'] = "Enter a valid 10-digit phone number.";
    }

    if ($sender_email !== '' && !filter_var($sender_email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors['sender_email'] = "Invalid email format.";
    }

    if ($pickup_address === '') {
        $validationErrors['pickup_address'] = "Pickup address is required.";
    }

    // --- RECEIVER VALIDATION ---
    if ($receiver_name === '') {
        $validationErrors['receiver_name'] = "Receiver name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $receiver_name)) {
        $validationErrors['receiver_name'] = "Numbers/symbols not allowed in names.";
    }

    if ($receiver_phone === '') {
        $validationErrors['receiver_phone'] = "Receiver phone is required.";
    } elseif (!preg_match("/^[0-9]{10}$/", $receiver_phone)) {
        $validationErrors['receiver_phone'] = "Enter a valid 10-digit phone number.";
    }

    if ($receiver_email !== '' && !filter_var($receiver_email, FILTER_VALIDATE_EMAIL)) {
        $validationErrors['receiver_email'] = "Invalid email format.";
    }

    if ($delivery_address === '') {
        $validationErrors['delivery_address'] = "Delivery address is required.";
    }

    if ($package_type === '') {
        $validationErrors['package_type'] = "Please select a package type.";
    }

    if (!empty($validationErrors)) {
        $_SESSION['form_errors'] = $validationErrors;
        $_SESSION['old'] = $_POST;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $tracking_id = 'TRK' . strtoupper(substr(md5(uniqid()), 0, 8));
    $status = 'Pending';

    // AGENT ASSIGNMENT LOGIC
    $activeStatuses = array('Pending','Assigned','In Transit');
    $statusesList = "'" . implode("','", $activeStatuses) . "'";

    $agentRes = $conn->query(
        "SELECT id FROM agents WHERE id NOT IN (
            SELECT agent_id FROM neworders WHERE status IN ($statusesList) AND agent_id IS NOT NULL
         ) ORDER BY id ASC LIMIT 1"
    );

    if (!$agentRes || $agentRes->num_rows === 0) {
        $agentRes = $conn->query(
            "SELECT a.id FROM agents a LEFT JOIN (
                SELECT agent_id, COUNT(*) AS total_orders FROM neworders GROUP BY agent_id
            ) t ON a.id = t.agent_id ORDER BY COALESCE(t.total_orders,0) ASC, a.id ASC LIMIT 1"
        );
    }

    $agentData = $agentRes ? $agentRes->fetch_assoc() : null;
    $agent_id = $agentData['id'] ?? NULL;

    $sql = "INSERT INTO neworders (user_id, sender_name, sender_phone, sender_email, pickup_address, receiver_name, receiver_phone, receiver_email, delivery_address, package_type, instructions, status, tracking_id, agent_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssssssssi", $user_id, $sender_name, $sender_phone, $sender_email, $pickup_address, $receiver_name, $receiver_phone, $receiver_email, $delivery_address, $package_type, $instructions, $status, $tracking_id, $agent_id);

    if ($stmt->execute()) {
        $_SESSION['successMsg'] = "✅ Order placed! Tracking ID: $tracking_id";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $_SESSION['errorMsg'] = "❌ Failed to save order.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>New Delivery Order - ShipManager</title>
<style>
/* ... Keeping your exact styles ... */
body { font-family: 'Poppins', sans-serif; margin: 0; padding: 0; min-height: 100vh; background: linear-gradient(135deg,#004aad,#0077b6); display: flex; justify-content: center; align-items: center; }
.container { background: rgba(255,255,255,0.85); backdrop-filter: blur(15px); padding: 40px; border-radius: 20px; max-width: 600px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.15); animation: fadeIn 0.8s ease, popIn 0.5s ease; }
h2 { text-align: center; margin-bottom: 25px; font-size: 1.9rem; font-weight: bold; background: linear-gradient(135deg,#004aad,#0077b6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
.form-group { margin-bottom: 15px; display: flex; flex-direction: column; }
label { font-weight: 600; margin-bottom: 6px; color: #0B2E33; font-size: 0.95rem; }
input, select, textarea { width: 100%; padding: 12px 14px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.2); font-size: 1rem; background: rgba(255,255,255,0.8); color: #0B2E33; transition: all 0.3s ease; box-sizing: border-box; }
textarea { height: 80px; resize: none; }
select { appearance: none; -webkit-appearance: none; -moz-appearance: none; background: rgba(255,255,255,0.8) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpolygon points='0,0 16,0 8,8' fill='%230077b6'/%3E%3C/svg%3E") no-repeat right 12px center; background-size: 12px; padding-right: 30px; }
input:focus, select:focus, textarea:focus { outline: none; border-color: #004aad; background: #fff; box-shadow: 0 0 8px rgba(0,77,173,0.3); }
button { width: 100%; background: linear-gradient(135deg,#004aad,#0077b6); color: #fff; border: none; padding: 15px; border-radius: 12px; font-size: 1rem; font-weight: bold; cursor: pointer; transition: transform 0.2s ease, background 0.3s ease; }
button:hover { transform: scale(1.02); background: linear-gradient(135deg,#003580,#005f99); }
.success-message { background:#d1e7dd; color:#0f5132; border:1px solid #badbcc; margin-bottom: 15px; padding: 12px; border-radius: 8px; text-align: center; font-weight: 500; }
.error-message { background:#f8d7da; color:#842029; border:1px solid #f5c2c7; margin-bottom: 15px; padding: 12px; border-radius: 8px; text-align: center; font-weight: 500; }
.error-field { border: 2px solid #dc3545 !important; }
.field-error-text { color: #dc3545; font-size: 0.85rem; margin-top: 6px; font-weight: 500;}
@keyframes fadeIn { from { opacity:0; transform:translateY(-20px); } to { opacity:1; transform:translateY(0); } }
@keyframes popIn { from { transform:scale(0.95); opacity:0; } to { transform:scale(1); opacity:1; } }
</style>
</head>
<body>

<div class="container">
<h2>Create New Delivery Order</h2>

<?php if($successMsg): ?><div class="success-message"><?php echo $successMsg; ?></div><?php endif; ?>
<?php if($errorMsg): ?><div class="error-message"><?php echo $errorMsg; ?></div><?php endif; ?>

<form method="POST" autocomplete="off" id="orderForm" novalidate>

  <h3 style="color:#004aad; margin-bottom:10px; font-weight:600;">Sender Details</h3>

  <div class="form-group">
    <label>Sender Full Name*</label>
    <input type="text" name="sender_name" placeholder="Enter sender name" 
        value="<?php echo htmlspecialchars($old['sender_name'] ?? ''); ?>"
        class="<?php echo isset($errors['sender_name']) ? 'error-field' : ''; ?>">
    <?php if (isset($errors['sender_name'])): ?>
        <div class="field-error-text"><?php echo $errors['sender_name']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Sender Phone*</label>
    <input type="tel" name="sender_phone" placeholder="e.g. 9812345678" 
        value="<?php echo htmlspecialchars($old['sender_phone'] ?? ''); ?>"
        class="<?php echo isset($errors['sender_phone']) ? 'error-field' : ''; ?>">
    <?php if (isset($errors['sender_phone'])): ?>
        <div class="field-error-text"><?php echo $errors['sender_phone']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Sender Email</label>
    <input type="email" name="sender_email" placeholder="Optional"
        value="<?php echo htmlspecialchars($old['sender_email'] ?? ''); ?>"
        class="<?php echo isset($errors['sender_email']) ? 'error-field' : ''; ?>">
    <?php if (isset($errors['sender_email'])): ?>
        <div class="field-error-text"><?php echo $errors['sender_email']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Pickup Address*</label>
    <textarea name="pickup_address" placeholder="Enter pickup address"
        class="<?php echo isset($errors['pickup_address']) ? 'error-field' : ''; ?>"><?php echo htmlspecialchars($old['pickup_address'] ?? ''); ?></textarea>
    <?php if (isset($errors['pickup_address'])): ?>
        <div class="field-error-text"><?php echo $errors['pickup_address']; ?></div>
    <?php endif; ?>
  </div>

  <hr style="border:0; border-top:2px solid rgba(0,77,173,0.3); margin:25px 0;">

  <h3 style="color:#004aad; margin-bottom:10px; font-weight:600;">Receiver Details</h3>

  <div class="form-group">
    <label>Receiver Full Name*</label>
    <input type="text" name="receiver_name" placeholder="Enter receiver name" 
        value="<?php echo htmlspecialchars($old['receiver_name'] ?? ''); ?>"
        class="<?php echo isset($errors['receiver_name']) ? 'error-field' : ''; ?>">
    <?php if (isset($errors['receiver_name'])): ?>
        <div class="field-error-text"><?php echo $errors['receiver_name']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Receiver Phone*</label>
    <input type="tel" name="receiver_phone" placeholder="e.g. 9812345678" 
        value="<?php echo htmlspecialchars($old['receiver_phone'] ?? ''); ?>"
        class="<?php echo isset($errors['receiver_phone']) ? 'error-field' : ''; ?>">
    <?php if (isset($errors['receiver_phone'])): ?>
        <div class="field-error-text"><?php echo $errors['receiver_phone']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Receiver Email</label>
    <input type="email" name="receiver_email" placeholder="Optional"
        value="<?php echo htmlspecialchars($old['receiver_email'] ?? ''); ?>"
        class="<?php echo isset($errors['receiver_email']) ? 'error-field' : ''; ?>">
    <?php if (isset($errors['receiver_email'])): ?>
        <div class="field-error-text"><?php echo $errors['receiver_email']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Delivery Address*</label>
    <textarea name="delivery_address" placeholder="Enter delivery address"
        class="<?php echo isset($errors['delivery_address']) ? 'error-field' : ''; ?>"><?php echo htmlspecialchars($old['delivery_address'] ?? ''); ?></textarea>
    <?php if (isset($errors['delivery_address'])): ?>
        <div class="field-error-text"><?php echo $errors['delivery_address']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Package Type*</label>
    <select name="package_type" class="<?php echo isset($errors['package_type']) ? 'error-field' : ''; ?>">
      <option value="">Select package type</option>
      <option value="Documents" <?php echo (isset($old['package_type']) && $old['package_type']==='Documents') ? 'selected' : ''; ?>>Documents</option>
      <option value="Electronics" <?php echo (isset($old['package_type']) && $old['package_type']==='Electronics') ? 'selected' : ''; ?>>Electronics</option>
      <option value="Clothing" <?php echo (isset($old['package_type']) && $old['package_type']==='Clothing') ? 'selected' : ''; ?>>Clothing</option>
      <option value="Fragile" <?php echo (isset($old['package_type']) && $old['package_type']==='Fragile') ? 'selected' : ''; ?>>Fragile</option>
      <option value="Bulky" <?php echo (isset($old['package_type']) && $old['package_type']==='Bulky') ? 'selected' : ''; ?>>Bulky</option>
      <option value="Other" <?php echo (isset($old['package_type']) && $old['package_type']==='Other') ? 'selected' : ''; ?>>Other</option>
    </select>
    <?php if (isset($errors['package_type'])): ?>
        <div class="field-error-text"><?php echo $errors['package_type']; ?></div>
    <?php endif; ?>
  </div>

  <div class="form-group">
    <label>Special Instructions</label>
    <textarea name="instructions" placeholder="Optional instructions"><?php echo htmlspecialchars($old['instructions'] ?? ''); ?></textarea>
  </div>

  <button type="submit">Place Order</button>
</form>
</div>

<script>
(function(){
    const form = document.getElementById("orderForm");
    if (!form) return;

    form.addEventListener("submit", function(e) {
        let isValid = true;
        const invalidInputs = [];

        // Remove previous client-side errors
        document.querySelectorAll(".field-error-text").forEach(el => el.remove());
        document.querySelectorAll(".error-field").forEach(el => el.classList.remove("error-field"));

        function showError(input, message) {
            input.classList.add("error-field");
            const errorDiv = document.createElement("div");
            errorDiv.className = "field-error-text";
            errorDiv.innerText = message;
            input.parentNode.appendChild(errorDiv);
            isValid = false;
            invalidInputs.push(input);
        }

        const nameRegex = /^[a-zA-Z\s]+$/;
        const phoneRegex = /^[0-9]{10}$/;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        // Validating Sender
        const sName = form.elements['sender_name'];
        if (!sName.value.trim()) {
            showError(sName, "Sender name is required.");
        } else if (!nameRegex.test(sName.value.trim())) {
            showError(sName, "Names cannot contain numbers.");
        }

        const sPhone = form.elements['sender_phone'];
        if (!sPhone.value.trim()) {
            showError(sPhone, "Sender phone is required.");
        } else if (!phoneRegex.test(sPhone.value.trim())) {
            showError(sPhone, "Enter a valid 10-digit phone number.");
        }

        const sEmail = form.elements['sender_email'];
        if (sEmail.value.trim() !== "" && !emailRegex.test(sEmail.value.trim())) {
            showError(sEmail, "Invalid email format.");
        }

        const pAddr = form.elements['pickup_address'];
        if (!pAddr.value.trim()) showError(pAddr, "Pickup address is required.");

        // Validating Receiver
        const rName = form.elements['receiver_name'];
        if (!rName.value.trim()) {
            showError(rName, "Receiver name is required.");
        } else if (!nameRegex.test(rName.value.trim())) {
            showError(rName, "Names cannot contain numbers.");
        }

        const rPhone = form.elements['receiver_phone'];
        if (!rPhone.value.trim()) {
            showError(rPhone, "Receiver phone is required.");
        } else if (!phoneRegex.test(rPhone.value.trim())) {
            showError(rPhone, "Enter a valid 10-digit phone number.");
        }

        const rEmail = form.elements['receiver_email'];
        if (rEmail.value.trim() !== "" && !emailRegex.test(rEmail.value.trim())) {
            showError(rEmail, "Invalid email format.");
        }

        const dAddr = form.elements['delivery_address'];
        if (!dAddr.value.trim()) showError(dAddr, "Delivery address is required.");

        const pType = form.elements['package_type'];
        if (!pType.value) showError(pType, "Please select a package type.");

        if (!isValid) {
            e.preventDefault();
            invalidInputs[0].focus();
        }
    });

    // Clear error message when user starts typing
    form.querySelectorAll('input, textarea, select').forEach(el => {
        el.addEventListener('input', function() {
            this.classList.remove('error-field');
            const err = this.parentNode.querySelector('.field-error-text');
            if (err) err.remove();
        });
    });
})();
</script>
</body>
</html>