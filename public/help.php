<?php require dirname(__DIR__) . '/src/bootstrap.php'; page_start('Help & FAQs'); ?>
<div class="prose">
    <p class="eyebrow">A LITTLE HELP GOES A LONG WAY</p>
    <h1>Make yourself at home.</h1>
    <div class="paper">
        <h2>How do I get started?</h2>
        <p>Create an account with your name, admission number and IIT (ISM) email. Sign in, choose
            an activity and select “Reserve my place”. Your confirmed places appear in <a
                href="/registrations.php"
            >My registrations</a>.</p>
    </div>
    <div class="paper">
        <h2>Can I register twice?</h2>
        <p>Each student can hold one place per activity. Your admission number and email can each
            belong to only one account. When an activity is full, new registrations close until
            someone cancels.</p>
    </div>
    <div class="paper">
        <h2>What if my plans change?</h2>
        <p>Open the activity or your registration list and cancel before the start time. Your place
            becomes available immediately. After an activity starts, registration changes close.</p>
    </div>
    <div class="paper">
        <h2>How is participation recorded?</h2>
        <p>Registration confirms your place. After the activity starts, its organizer can mark you
            as attended. You can see attendance and any activity cancellation in your registration
            list.</p>
    </div>
    <div
        id="access"
        class="paper"
    >
        <h2>Trouble signing in?</h2>
        <p>Use the institute email and password you chose for ClubSphere. Five unsuccessful attempts
            pause sign-in and recovery for 15 minutes. Sessions expire after an hour of inactivity.
        </p>
        <p>If you are signed in, change your password under <a href="/profile.php">Your account</a>.
            If you have forgotten it, use <a href="/recovery.php">account recovery</a> with the
            single-use code shown when you signed up. A successful reset signs out previous sessions
            and gives you a replacement code. If you have lost both your password and code, contact
            the portal administrator in person for an identity check. Email recovery is not
            configured.</p>
        <p>This portal uses its own accounts. Do not enter your institute account password.</p>
    </div>
    <div class="paper">
        <h2>How do organizers publish activities?</h2>
        <p>Organizer access is assigned by the portal administrator after a student account is
            created. Authorized organizers see “Organize” in the navigation, where they can publish
            activities, edit their own upcoming activities, view participants and record attendance.
            Each activity can include a JPEG, PNG or WebP poster up to 2 MB. Edit an activity to
            replace or remove its poster. Students can select the eye icon on its card to view
            the full image, then close it with the Close poster button or Escape.
        </p>
    </div>
    <div class="paper">
        <h2>About this portal</h2>
        <p>ClubSphere is a student project for IIT (ISM) Dhanbad. It is not an official institute
            authentication or announcements service. Clearly labelled sample activities are
            demonstration content and are not scheduled institute events. All activity times use
            IST.</p>
    </div>
</div>
<?php page_end(); ?>
