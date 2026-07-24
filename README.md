env.example provides an example of what you need to put into the .env file on your system to keep your secrets (Canvas Token, etc.) safe.  Put it in a secure location on your server.

It currently contains the course id, Canvas assignment id, and grade to be posted.  In your auto grader, you'd want these to vary per assignment as shown by how the user id is handled with a look up to a resource like students.csv or a database.
