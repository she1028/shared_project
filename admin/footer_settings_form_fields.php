<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">Footer Logo</label>
    <input type="file" class="form-control logo-file" name="logo_file" accept="image/*">
    <input type="hidden" class="logo-path" name="logo_path" value="">
    <div class="form-text">Upload an image to use as the footer logo.</div>
    <div class="mt-2">
      <img class="logo-preview" src="" alt="Logo preview" style="display:none; width:90px; height:90px; object-fit:contain; background:#fff; border-radius:8px;">
      <div class="small text-muted mt-1 logo-current" style="display:none;"></div>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Business Name</label>
    <input type="text" class="form-control" name="business_name" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Copyright Text</label>
    <input type="text" class="form-control" name="copyright_text" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Business Hours</label>
    <input type="text" class="form-control" name="business_hours" required>
  </div>

  <div class="col-12">
    <label class="form-label">Address</label>
    <textarea class="form-control" name="address" rows="3" required></textarea>
  </div>

  <div class="col-md-6">
    <label class="form-label">Catering Phone</label>
    <input type="text" class="form-control" name="catering_phone" required>
  </div>

  <div class="col-md-6">
    <label class="form-label">Food Order Phone</label>
    <input type="text" class="form-control" name="food_order_phone" required>
  </div>

  <div class="col-md-4">
    <label class="form-label">Instagram URL</label>
    <input type="text" class="form-control" name="instagram_url" placeholder="https://instagram.com/...">
  </div>

  <div class="col-md-4">
    <label class="form-label">X URL</label>
    <input type="text" class="form-control" name="x_url" placeholder="https://x.com/...">
  </div>

  <div class="col-md-4">
    <label class="form-label">Facebook URL</label>
    <input type="text" class="form-control" name="facebook_url" placeholder="https://facebook.com/...">
  </div>
</div>
