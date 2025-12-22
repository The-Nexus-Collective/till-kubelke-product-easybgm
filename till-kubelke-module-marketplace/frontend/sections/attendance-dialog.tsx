import type { IPartnerEngagement } from "src/types/marketplace";

import { useCallback } from "react";

import Box from "@mui/material/Box";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import Typography from "@mui/material/Typography";
import DialogTitle from "@mui/material/DialogTitle";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";

import {
  cancelParticipation,
  useGetParticipations,
  markParticipantNoShow,
  markParticipantAttended,
} from "src/actions/marketplace";

import { Iconify } from "src/components/iconify";

import { AttendanceTracker } from "./attendance-tracker";

// ----------------------------------------------------------------------

type Props = {
  open: boolean;
  onClose: () => void;
  engagement: IPartnerEngagement;
};

export function AttendanceDialog({ open, onClose, engagement }: Props) {
  const { participations, participationsLoading, participationsMutate } =
    useGetParticipations(engagement.id);

  const handleMarkAttended = useCallback(
    async (id: number) => {
      await markParticipantAttended(id);
      await participationsMutate();
    },
    [participationsMutate],
  );

  const handleMarkNoShow = useCallback(
    async (id: number) => {
      await markParticipantNoShow(id);
      await participationsMutate();
    },
    [participationsMutate],
  );

  const handleMarkCancelled = useCallback(
    async (id: number) => {
      await cancelParticipation(id);
      await participationsMutate();
    },
    [participationsMutate],
  );

  const handleBulkMarkAttended = useCallback(
    async (ids: number[]) => {
      // Execute all in parallel
      await Promise.all(ids.map((id) => markParticipantAttended(id)));
      await participationsMutate();
    },
    [participationsMutate],
  );

  // Calculate summary for header
  const totalCount = participations.length;
  const attendedCount = participations.filter(
    (p) => p.status === "attended",
  ).length;

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="lg"
      fullWidth
      PaperProps={{
        sx: { maxHeight: "90vh" },
      }}
    >
      <DialogTitle>
        <Stack direction="row" alignItems="center" spacing={2}>
          <Iconify icon="solar:users-group-rounded-bold" width={28} />
          <Box>
            <Typography variant="h6">Teilnehmer-Tracking</Typography>
            <Typography variant="body2" color="text.secondary">
              {engagement.offeringTitle} – {engagement.providerName}
            </Typography>
          </Box>
          <Box sx={{ flexGrow: 1 }} />
          {totalCount > 0 && (
            <Typography variant="body2" color="text.secondary">
              {attendedCount} / {totalCount} teilgenommen
            </Typography>
          )}
        </Stack>
      </DialogTitle>

      <DialogContent dividers sx={{ p: 0 }}>
        <AttendanceTracker
          participations={participations}
          isLoading={participationsLoading}
          onMarkAttended={handleMarkAttended}
          onMarkNoShow={handleMarkNoShow}
          onMarkCancelled={handleMarkCancelled}
          onBulkMarkAttended={handleBulkMarkAttended}
          showEmployeeDetails
        />
      </DialogContent>

      <DialogActions>
        <Button variant="outlined" onClick={onClose}>
          Schließen
        </Button>
      </DialogActions>
    </Dialog>
  );
}

