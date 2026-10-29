<?php require dirname(__DIR__) . '/src/bootstrap.php'; page_start('Privacy'); ?>
<div class="prose">
    <p class="eyebrow">YOUR INFORMATION</p>
    <h1>Privacy, in plain words.</h1>
    <div class="paper">
        <h2>What the portal stores</h2>
        <p>ClubSphere stores your name, admission number, institute email, password hash, a hash of
            your recovery code, activity registrations and recorded attendance. Your password is
            hashed, and your original password is not stored.</p>
        <h2>Who can see it</h2>
        <p>You can view your own profile and registrations. The organizer of an activity can see the
            names, admission numbers and institute emails of students registered for that activity,
            along with their attendance. The portal administrator can access the underlying
            database.</p>
        <h2>Why it is used</h2>
        <p>These details support sign-in, account recovery, duplicate prevention, activity capacity
            and participation records. Public activity pages show aggregate availability, not
            participant details.</p>
        <h2>Sessions and hosting</h2>
        <p>A required session cookie keeps you signed in. It is cleared when you sign out. The
            website and database run on Render. No advertising or analytics services are embedded.
            The institute website link opens an external website.</p>
        <h2>Corrections and removal</h2>
        <p>You can update your name and password from your account. Contact the portal administrator
            for admission or email corrections, account removal, or record questions. Student
            accounts remain until removed by the administrator; there is no automatic retention
            schedule.</p>
        <h2>Student project notice</h2>
        <p>This project does not verify institute email ownership. Do not reuse an institute
            password or submit sensitive documents. Sample activities are for demonstration.</p>
    </div>
</div>
<?php page_end(); ?>
