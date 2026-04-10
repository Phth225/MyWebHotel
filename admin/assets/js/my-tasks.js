// Auto dismiss alerts
document.addEventListener("DOMContentLoaded", function () {
  setTimeout(() => {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
      const bsAlert =
        bootstrap.Alert.getInstance(alert) || new bootstrap.Alert(alert);
      bsAlert.close();
    });
  }, 5000);
});
