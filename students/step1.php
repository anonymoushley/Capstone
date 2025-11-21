<div id="step1" class="card mb-4">
    <div class="card-header bg-success text-white">
            <h5 class="mb-0">Step 1: Personal Information</h5>
        </div>
      <div class="card-body">
        <h5></h5>
        <div class="id-picture-container" id="previewContainer" style="cursor: pointer;" onclick="document.getElementById('idPicture').click();">
          <div class="placeholder-text"> Insert 2x2 ID</div>
          <img id="picturePreview" name="id_picture" class="id-picture-preview" alt="ID Preview">
        </div>

        <div class="mb-3">
          <label class="form-label">2x2 ID Picture</label>
          <input type="file" name="id_picture" id="idPicture" class="form-control" accept="image/*" required style="display: none;">
          <div class="form-text">Click the preview box in the upper right to upload your 2x2 ID picture</div>
        </div>
        <div class="row mt-5">
          <div class="col-md-4 mb-3">
            <label class="form-label">Last Name</label>
      <input type="text" name="last_name" class="form-control all-uppercase" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">First Name</label>
      <input type="text" name="first_name" class="form-control all-uppercase" required>
    </div>
    <div class="col-md-4 mb-3">
      <label class="form-label">Middle Name</label>
      <input type="text" name="middle_name" class="form-control all-uppercase">
    </div>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
  <label class="form-label">Date of Birth</label>
  <input type="date" name="birth_date" class="form-control" id="birthDate" required onchange="calculateAge()">
</div>
<div class="col-md-2 mb-3">
  <label class="form-label">Age</label>
  <input type="text" name="age" class="form-control" id="age" required readonly>
</div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Sex</label>
            <select name="sex" class="form-select" required>
            <option value="" disabled selected hidden>Select</option>
              <option value="Male">Male</option>
              <option value="Female">Female</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
  <label class="form-label">Contact Number</label>
  <input
    type="text"
    name="contact_number"
    class="form-control"
    maxlength="11"
    required
    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);"
  >
</div>

</div>
<div class="col-md-4 mb-3">
  <label for="region" class="form-label">Region</label>
  <select id="region" name="region" class="form-select" required>
    <option value="" disabled selected hidden>Select Region</option>
  </select>
</div>

<div class="col-md-4 mb-3">
  <label for="province" class="form-label">Province</label>
  <select id="province" name="province" class="form-select" required disabled>
    <option value="" disabled selected hidden>No Selected Region</option>
  </select>
</div>


        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">City</label>
            <select id="city" name="city" class="form-select" required disabled>
    <option value="">No Province Selected</option>
  </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Barangay</label>
            <select id="barangay" name="barangay" class="form-select" required disabled>
  <option value="">No City/Municipality Selected   </option>
</select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Street / Purok</label>
            <input type="text" name="street" class="form-control" required>
          </div>
        </div>

        <form action="sociodemographic.php" method="POST">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Socio Demographic Profile</h5>
        </div>
        <div class="card-body p-4 mb-4">

          <div class="mb-3">
            <label class="d-block"><b>Marital Status:</b></label>
            <div class="d-flex flex-nowrap overflow-auto gap-3 p-1">
              <?php foreach(["Single", "Married", "Divorced", "Domestic Partnership", "Others"] as $opt): ?>
                <div class="form-check form-check-inline">
                  <input type="radio" class="form-check-input" name="marital_status" value="<?= $opt ?>" required>
                  <label class="form-check-label"><?= $opt ?></label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <hr>

          <label><b>Religious Affiliation:</b></label><br>
          <?php foreach(["None", "Christianity", "Islam", "Hinduism", "Others"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="religion" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Sexual Orientation:</b></label><br>
          <?php foreach(["Heterosexual", "Homosexual", "Bisexual", "Others"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="orientation" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Parental Status</h5>
        </div>
        <div class="card-body p-4 mb-4">
          <label><b>Father Status:</b></label><br>
          <?php foreach(["Alive; Away", "Alive; at Home", "Deceased", "Unknown"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="father_status" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Father Education Level:</b></label><br>
          <?php foreach(["No High School Diploma", "High School Diploma", "Bachelor's Degree", "Graduate Degree"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="father_education" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Father Employment:</b></label><br>
          <?php foreach(["Employed Full-Time", "Employed Part-Time", "Unemployed"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="father_employment" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Mother Status:</b></label><br>
          <?php foreach(["Alive; Away", "Alive; at Home", "Deceased", "Unknown"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="mother_status" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Mother Education Level:</b></label><br>
          <?php foreach(["No High School Diploma", "High School Diploma", "Bachelor's Degree", "Graduate Degree"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="mother_education" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Mother Employment:</label></b><br>
          <?php foreach(["Employed Full-Time", "Employed Part-Time", "Unemployed"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="mother_employment" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Other Details</h5>
        </div>
        <div class="card-body p-4 mb-4">
          <label><b>Number of Siblings:</b></label><br>
          <?php foreach(["None", "One", "Two or more"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="siblings" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>

          <hr>

          <label><b>Currently Living With:</b></label><br>
          <?php foreach(["Both parents", "One parent only", "Relatives", "Alone"] as $opt): ?>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="living_with" value="<?= $opt ?>" required>
              <label class="form-check-label"><?= $opt ?></label>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Technology Access</h5>
        </div>
        <div class="card-body p-4 mb-2">
          <?php
            $tech = [
              "access_computer" => "Access to personal computer at home",
              "access_internet" => "Internet access at home",
              "access_mobile" => "Access to mobile device(s)"
            ];
            foreach ($tech as $name => $label):
          ?>
            <label><b><?= $label ?>:</b></label><br>
            <div class="form-check form-check-inline">
              <input type="radio" class="form-check-input" name="<?= $name ?>" value="1" required>
              <label class="form-check-label">Yes</label>
            </div>
            <div class="form-check form-check-inline mb-2">
              <input type="radio" class="form-check-input" name="<?= $name ?>" value="0">
              <label class="form-check-label">No</label>
            </div><br>
          <?php endforeach; ?>
        </div>
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Other Determinants</h5>
        </div>
        <div class="card-body p-4 mb-4">
  <div class="row">
    <?php
      $other = [
        "indigenous_group" => "Member of an indigenous group",
        "first_gen_college" => "First in family to attend college",
        "was_scholar" => "Scholar during high school",
        "received_honors" => "Received academic honors",
        "has_disability" => "Has a disability"
      ];

      foreach ($other as $name => $label):
    ?>
      <div class="col-md-6 mb-3">
        <label><b><?= $label ?>:</b></label><br>
        <div class="form-check form-check-inline">
          <input type="radio" class="form-check-input" id="<?= $name ?>_yes" name="<?= $name ?>" value="1" <?= $name == 'has_disability' ? 'onclick="toggleDisabilityDetail(true)"' : '' ?> required>
          <label class="form-check-label">Yes</label>
        </div>
        <div class="form-check form-check-inline">
          <input type="radio" class="form-check-input" id="<?= $name ?>_no" name="<?= $name ?>" value="0" <?= $name == 'has_disability' ? 'onclick="toggleDisabilityDetail(false)"' : '' ?>>
          <label class="form-check-label">No</label>
        </div>
      </div>

      <?php if ($name == "has_disability"): ?>
        <div class="col-md-6 mb-3" id="disability_detail" style="display:none;">
          <label>If yes, specify disability:</label>
          <input type="text" class="form-control" name="disability_detail">
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
</div>


        <button type="button" class="btn btn-success" onclick="showProfilingStep(2, document.querySelectorAll('#profilingTabs .nav-link')[1])">Next</button>
      </div>
    </div>