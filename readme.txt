=== Sbytes Restrict Preview for Tutor LMS ===
Contributors: salmamohamed25
Tags: tutor lms, lms, courses, registration, preview
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ask visitors to log in or register before they watch free preview lessons in Tutor LMS, then send them straight to the lesson.

== Description ==

Tutor LMS lets you mark lessons as free previews so anyone can watch them. That is great for marketing, but it means visitors can consume your sample content without ever telling you who they are.

Sbytes Restrict Preview for Tutor LMS asks for a free account first. Guests see a tabbed Log In / Sign Up form in place of the lesson, and once they are in they land on the exact lesson they clicked — no dead ends, no hunting through the course again.

= What it does =

* Requires visitors to be logged in before viewing preview lessons.
* Shows a login and registration form together, in tabs, without leaving the page.
* Returns the visitor to the lesson they wanted after they log in or sign up.
* Optionally badges preview lessons in the curriculum and shows a free-lesson count on each section.

= Who is never affected =

Enrolled students, instructors and administrators keep the access they already have. Only logged-out visitors are asked to sign in. Lessons that are **not** marked as previews are left completely alone, so Tutor LMS's normal enrolment rules continue to apply to them unchanged.

= Built on Tutor LMS itself =

The plugin does not reimplement authentication. It embeds Tutor's own `[tutor_login]` and `[tutor_student_registration_form]` shortcodes, so validation, password handling and account creation all stay in Tutor where they belong. The redirect uses Tutor's own supported `redirect_to` field.

Access is enforced on the server, so the restriction cannot be bypassed by opening the lesson URL directly or by disabling JavaScript.

== Installation ==

1. Install and activate Tutor LMS if you have not already.
2. Upload the plugin to `/wp-content/plugins/`, or install it from the Plugins screen.
3. Activate the plugin.
4. Visit **Settings → Restrict Preview** to adjust the wording, colour and options.

To offer sign up as well as log in, enable **Anyone can register** under **Settings → General**. Without it, Tutor's registration handler will not process new accounts, so the Sign Up tab hides itself automatically.

== Frequently Asked Questions ==

= Does this restrict paid or enrolled course content? =

No. It only affects lessons that are marked as free previews in Tutor LMS. Every other lesson keeps Tutor's normal enrolment gating, untouched.

= Can someone bypass it by pasting the lesson URL? =

No. The content is replaced on the server before the page is sent, so a direct URL, a disabled-JavaScript browser or a crawler all get the same prompt.

= Why is the Sign Up tab missing? =

WordPress registration is switched off. Turn on **Anyone can register** under **Settings → General**. The tab is hidden rather than shown as a dead end, because Tutor's registration handler refuses to create accounts while that setting is off.

= The badges are not appearing on my course page. =

Badges are matched to real preview lessons by their URL, and they need a link to attach to. Themes that render the curriculum with heavily customised markup may not expose one. The plugin never guesses from the rendered text, because many courses never display the word "Free" at all.

= Does it work with page builders? =

The server-side gate works everywhere. If your curriculum is rendered somewhere unusual and the assets do not load, use the `rptl_load_assets` filter to force them on.

= Can I change who is allowed through? =

Yes, with the `rptl_user_may_view_preview` filter. It receives a boolean and lets you widen or narrow access.

== Screenshots ==

1. The login and signup popup shown when a guest clicks a preview lesson.
2. A locked preview lesson page with the form inline.
3. Free preview badges in the course curriculum.
4. The settings screen.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
