<?php
require_once '../includes/auth_check.php';
checkRole('lecturer');
require_once '../config/db.php';

$course_id = intval($_GET['id'] ?? 0);
$lecturer_id = $_SESSION['user_id'];

$course = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM courses WHERE course_id=$course_id AND lecturer_id=$lecturer_id"));
if (!$course) {
    die("Course not found or you don't have permission to view it.");
}

$materials = mysqli_query($conn, "SELECT * FROM materials WHERE course_id=$course_id ORDER BY uploaded_at DESC");
$assignments = mysqli_query($conn, "SELECT * FROM assignments WHERE course_id=$course_id ORDER BY deadline DESC");
$quizzes = mysqli_query($conn, "SELECT * FROM quizzes WHERE course_id=$course_id ORDER BY created_at DESC");
$students = mysqli_query($conn, "
  SELECT u.user_id, u.full_name, u.email, e.enrolled_at FROM enrollments e
  JOIN users u ON e.student_id = u.user_id
  WHERE e.course_id=$course_id ORDER BY e.enrolled_at DESC
");
$announcements = mysqli_query($conn, "SELECT * FROM announcements WHERE course_id=$course_id ORDER BY created_at DESC");

$page_title = $course['course_name'];
include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap">
  <div>
    <h3 class="section-title mb-0"><?php echo htmlspecialchars($course['course_name']); ?></h3>
    <p class="text-muted"><?php echo htmlspecialchars($course['course_code']); ?></p>
  </div>
  <a href="my_courses.php" class="btn btn-outline-secondary btn-sm">&larr; Back to My Courses</a>
</div>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success auto-alert"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#materials">Materials</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#assignments">Assignments</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#quizzes">Quizzes</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#students">Students (<?php echo mysqli_num_rows($students); ?>)</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#attendance">Attendance</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#announce">Announcements</button></li>
</ul>

<div class="tab-content">

  <!-- MATERIALS TAB -->
  <div class="tab-pane fade show active" id="materials">
    <div class="card p-3 mb-3">
      <h6 class="mb-3">Upload New Material</h6>
      <form method="POST" action="upload_material.php" enctype="multipart/form-data" class="row g-2">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="col-md-4">
          <input type="text" name="title" class="form-control" placeholder="Material title" required>
        </div>
        <div class="col-md-3">
          <select name="material_type" class="form-select">
            <option value="pdf">PDF / Document</option>
            <option value="video_link">Video Link</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="col-md-4">
          <input type="file" name="material_file" class="form-control" id="materialFile">
          <input type="text" name="video_link" class="form-control mt-1" placeholder="Or paste video URL">
        </div>
        <div class="col-md-1">
          <button class="btn btn-primary w-100">Add</button>
        </div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-dark"><tr><th>Title</th><th>Type</th><th>Uploaded</th><th>Actions</th></tr></thead>
        <tbody>
          <?php while ($m = mysqli_fetch_assoc($materials)): ?>
          <tr>
            <td><?php echo htmlspecialchars($m['title']); ?></td>
            <td><span class="badge bg-info text-dark"><?php echo str_replace('_',' ',$m['material_type']); ?></span></td>
            <td><?php echo date('M d, Y', strtotime($m['uploaded_at'])); ?></td>
            <td>
              <?php if ($m['material_type']=='video_link'): ?>
                <a href="<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
              <?php else: ?>
                <a href="/lms/assets/uploads/materials/<?php echo htmlspecialchars($m['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Download</a>
              <?php endif; ?>
              <a href="delete_material.php?id=<?php echo $m['material_id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-msg="Delete this material?">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ASSIGNMENTS TAB -->
  <div class="tab-pane fade" id="assignments">
    <div class="card p-3 mb-3">
      <h6 class="mb-3">Create New Assignment</h6>
      <form method="POST" action="add_assignment.php" class="row g-2">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="col-md-3"><input type="text" name="title" class="form-control" placeholder="Assignment title" required></div>
        <div class="col-md-3"><textarea name="description" class="form-control" placeholder="Description" rows="1"></textarea></div>
        <div class="col-md-3"><input type="datetime-local" name="deadline" class="form-control" required></div>
        <div class="col-md-2"><input type="number" name="max_marks" class="form-control" placeholder="Max marks" value="100"></div>
        <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-dark"><tr><th>Title</th><th>Deadline</th><th>Max Marks</th><th>Actions</th></tr></thead>
        <tbody>
          <?php while ($a = mysqli_fetch_assoc($assignments)): ?>
          <tr>
            <td><?php echo htmlspecialchars($a['title']); ?></td>
            <td><?php echo date('M d, Y h:i A', strtotime($a['deadline'])); ?></td>
            <td><?php echo $a['max_marks']; ?></td>
            <td>
              <a href="view_submissions.php?assignment_id=<?php echo $a['assignment_id']; ?>" class="btn btn-sm btn-outline-primary">Submissions</a>
              <a href="delete_assignment.php?id=<?php echo $a['assignment_id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-msg="Delete this assignment?">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- QUIZZES TAB -->
  <div class="tab-pane fade" id="quizzes">
    <div class="card p-3 mb-3">
      <h6 class="mb-3">Create New Quiz</h6>
      <form method="POST" action="add_quiz.php" class="row g-2">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="col-md-6"><input type="text" name="title" class="form-control" placeholder="Quiz title" required></div>
        <div class="col-md-4"><input type="number" name="duration_minutes" class="form-control" placeholder="Duration (minutes)" value="30" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-dark"><tr><th>Title</th><th>Duration</th><th>Questions</th><th>Actions</th></tr></thead>
        <tbody>
          <?php while ($q = mysqli_fetch_assoc($quizzes)):
            $qcount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM quiz_questions WHERE quiz_id={$q['quiz_id']}"))['c'];
          ?>
          <tr>
            <td><?php echo htmlspecialchars($q['title']); ?></td>
            <td><?php echo $q['duration_minutes']; ?> min</td>
            <td><?php echo $qcount; ?> questions</td>
            <td>
              <a href="manage_quiz_questions.php?quiz_id=<?php echo $q['quiz_id']; ?>" class="btn btn-sm btn-outline-primary">Manage Questions</a>
              <a href="quiz_results.php?quiz_id=<?php echo $q['quiz_id']; ?>" class="btn btn-sm btn-outline-success">Results</a>
              <a href="delete_quiz.php?id=<?php echo $q['quiz_id']; ?>&course_id=<?php echo $course_id; ?>" class="btn btn-sm btn-outline-danger confirm-delete" data-msg="Delete this quiz?">Delete</a>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- STUDENTS TAB -->
  <div class="tab-pane fade" id="students">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-dark"><tr><th>Name</th><th>Email</th><th>Enrolled On</th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($students) === 0): ?>
            <tr><td colspan="3" class="text-muted">No students enrolled yet.</td></tr>
          <?php endif; ?>
          <?php while ($s = mysqli_fetch_assoc($students)): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['full_name']); ?></td>
            <td><?php echo htmlspecialchars($s['email']); ?></td>
            <td><?php echo date('M d, Y', strtotime($s['enrolled_at'])); ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ATTENDANCE TAB -->
  <div class="tab-pane fade" id="attendance">
    <div class="card p-3">
      <h6 class="mb-3">Mark Attendance</h6>
      <form method="POST" action="mark_attendance.php">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="mb-3">
          <label class="form-label">Date</label>
          <input type="date" name="attendance_date" class="form-control" style="max-width:220px;" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <?php mysqli_data_seek($students, 0); ?>
        <table class="table">
          <thead><tr><th>Student</th><th>Present</th></tr></thead>
          <tbody>
          <?php while ($s = mysqli_fetch_assoc($students)): ?>
            <tr>
              <td><?php echo htmlspecialchars($s['full_name']); ?></td>
              <td><input type="checkbox" name="present[]" value="<?php echo $s['user_id']; ?>" checked></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        <button class="btn btn-primary">Save Attendance</button>
        <a href="view_attendance.php?course_id=<?php echo $course_id; ?>" class="btn btn-outline-secondary">View Attendance Records</a>
      </form>
    </div>
  </div>

  <!-- ANNOUNCEMENTS TAB -->
  <div class="tab-pane fade" id="announce">
    <div class="card p-3 mb-3">
      <h6 class="mb-3">Post Announcement to This Course</h6>
      <form method="POST" action="post_course_announcement.php" class="row g-2">
        <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
        <div class="col-md-4"><input type="text" name="title" class="form-control" placeholder="Title" required></div>
        <div class="col-md-6"><input type="text" name="message" class="form-control" placeholder="Message" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Post</button></div>
      </form>
    </div>
    <?php while ($an = mysqli_fetch_assoc($announcements)): ?>
      <div class="card p-3 mb-2">
        <h6 class="fw-bold"><?php echo htmlspecialchars($an['title']); ?></h6>
        <p class="mb-1"><?php echo htmlspecialchars($an['message']); ?></p>
        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($an['created_at'])); ?></small>
      </div>
    <?php endwhile; ?>
  </div>

</div>

<?php include '../includes/footer.php'; ?>
