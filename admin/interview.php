
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 14px;
    }
    h2 {
      text-align: center;
      margin-bottom: 10px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
      margin-left: 40px;
    }
    th, td {
      border: 1px solid #333;
      padding: 8px;
      text-align: center;
      vertical-align: middle;
      background-color: #f0f0f0;
    }
    th.section {
      background-color: #f0f0f0;
      text-align: left;
    }
    td.label {
      text-align: left;
    }
    .score-label {
      font-weight: bold;
      background-color: #f9f9f9;
    }
    .total-container {
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <h2>INTERVIEWER'S SECTION</h2>

  <form action="#" method="post">
    <table>
      <tr>
        <th class="section" colspan="7">I. PREPAREDNESS FOR COLLEGE EDUCATION (20 points)</th>
      </tr>
      <tr>
        <th style="width: 40%;">Indicators</th>
        <th class="score-label">5<br>Excellent</th>
        <th class="score-label">4<br>Above Average</th>
        <th class="score-label">3<br>Average</th>
        <th class="score-label">2<br>Below Avg</th>
        <th class="score-label">1<br>Poor</th>
      </tr>

      <!-- Preparedness Rows -->
      <?php
        $section1 = [
          'Foundation in math, science, and other requisite skills/knowledge',
          'Study habits (as discussed during the interview)',
          'Display of interest in the applied program',
          'Academic and extra-curricular achievements/awards'
        ];
        foreach ($section1 as $i => $item):
      ?>
        <tr>
          <td class="label"><?= ($i+1).'. '.$item ?></td>
          <?php for ($score = 5; $score >= 1; $score--): ?>
            <td><input type="radio" name="section1_item<?= $i ?>" value="<?= $score ?>" required></td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>

      <tr>
        <th class="section" colspan="7">II. ORAL COMMUNICATION SKILLS (20 points)</th>
      </tr>
      <?php
        $section2 = [
          'Content of the responses to interview questions',
          'Manner and delivery of the responses',
          'Mechanics, use of words/terms, grammar, and pronunciation',
          'Gestures and facial expressions'
        ];
        foreach ($section2 as $i => $item):
      ?>
        <tr>
          <td class="label"><?= ($i+1).'. '.$item ?></td>
          <?php for ($score = 5; $score >= 1; $score--): ?>
            <td><input type="radio" name="section2_item<?= $i ?>" value="<?= $score ?>" required></td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>

      <tr>
        <th class="section" colspan="7">III. PERSONAL/PHYSICAL/SOCIAL TRAITS (20 points)</th>
      </tr>
      <?php
        $section3 = [
          'Personal traits (professionalism, confidence, enthusiasm)',
          'Social traits (courtesy, attentiveness, rapport with interviewer)',
          'Physical appearance (hygiene, grooming, dress/clothes)',
          'Body language, eye contact, and posture'
        ];
        foreach ($section3 as $i => $item):
      ?>
        <tr>
          <td class="label"><?= ($i+1).'. '.$item ?></td>
          <?php for ($score = 5; $score >= 1; $score--): ?>
            <td><input type="radio" name="section3_item<?= $i ?>" value="<?= $score ?>" required></td>
          <?php endfor; ?>
        </tr>
      <?php endforeach; ?>

      <tr>
        <th class="section" colspan="7">IV. WRITING SKILLS (20 points)</th>
      </tr>
      <tr>
        <td class="label">1. Rating on Writing Skills</td>
        <?php for ($score = 5; $score >= 1; $score--): ?>
          <td><input type="radio" name="writing" value="<?= $score ?>" required></td>
        <?php endfor; ?>
      </tr>

      <tr>
        <th class="section" colspan="7">V. READING AND COMPREHENSION (20 points)</th>
      </tr>
      <tr>
        <td class="label">1. Score on Reading and Comprehension Test</td>
        <td colspan="6"><input type="number" name="reading_score" min="0" max="20" style="width: 100%;" required></td>
      </tr>

      <tr>
        <th colspan="7">TOTAL SCORE (TS): <input type="number" name="total_score" readonly style="width: 100px;"></th>
      </tr>
      <tr>
        <th colspan="7">
          INTERVIEW SCORE = (TS / 100) × 50 = 
          <input type="number" name="final_score" readonly style="width: 100px;">
        </th>
      </tr>
    </table>

    <button type="submit">Submit Evaluation</button>
  </form>

</body>
</html>
