# Gate 3 technical verification boundary

Tracking: #4

Gate 3 may verify technical authorization after Gate 1 is complete,
without waiting for Gate 2 policy approval. Keep Twill disabled and use
test-only accounts and permissions for protected reads and writes.

Gate 2 approval is still required before assigning real privileges or
deploying Gate 3 authorization changes. Gate 3 evidence does not approve
organizational roles, publishing authority, or deployment. Gates 4–8 retain
their existing cumulative prerequisites; this exception does not unblock them.
