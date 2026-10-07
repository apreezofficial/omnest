"use client";

import { useState, useTransition } from "react";
import { Button } from "@/components/ui/Button";
import { Card } from "@/components/ui/Card";
import { deleteChild } from "../../../actions";

export function DeleteChild({ childId, name }: { childId: number; name: string }) {
  const [confirming, setConfirming] = useState(false);
  const [pending, startTransition] = useTransition();

  return (
    <Card className="flex flex-col gap-4 p-6">
      <h2 className="type-h4">Remove {name}</h2>
      <p className="text-body-sm text-text-muted">
        This deletes {name}&apos;s profile and unpairs their phones. Limits stop working on those phones right away.
      </p>
      {confirming ? (
        <div className="flex flex-wrap gap-3">
          <Button variant="danger" disabled={pending} onClick={() => startTransition(() => deleteChild(childId))}>
            {pending ? "Removing..." : `Yes, remove ${name}`}
          </Button>
          <Button variant="secondary" disabled={pending} onClick={() => setConfirming(false)}>
            Cancel
          </Button>
        </div>
      ) : (
        <div>
          <Button variant="secondary" onClick={() => setConfirming(true)}>
            Remove {name}
          </Button>
        </div>
      )}
    </Card>
  );
}
