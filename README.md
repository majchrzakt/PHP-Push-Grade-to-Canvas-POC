# Overview and Demonstration
This is just a proof of concept, showing how a student might log into your tool (to be developed), which also would accept a file submission, grade it, and push a grade to Canvas for a given assignment.

To see it working watch this ([9 second video](https://www.youtube.com/watch?v=E5L4omuCnhg)).

# Recommendations
Then env.example provides an example of what you need to put into the .env file on your system to keep your secrets (Canvas Token, etc.) safe.  Put it in a secure location on your server outside of your public web folder.

It currently contains the course id, Canvas assignment id, and grade to be posted, but in your auto grader, you'd want these to vary per assignment as shown by how the user id is handled with a look up file resource like students.csv or a database.

This minimizes the amount of PII stored on the server to student emails and Canvas user ids, which are needed to push the grade to the correct student.  No grades are stored on the system.  Even less information could be stored on the server, if your institutiion allows you to create an LTI integration where a student can click a link in Canvas, which would provide context (name, email, Canvas id) to your program without the need for them to login to your system at all.  Canvas would provide the authentication.
