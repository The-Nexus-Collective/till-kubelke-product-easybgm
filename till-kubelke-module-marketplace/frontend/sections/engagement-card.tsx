import type { IPartnerEngagement } from "src/types/marketplace";

import { useState, useCallback } from "react";
import { useBoolean } from "minimal-shared/hooks";

import Box from "@mui/material/Box";
import Card from "@mui/material/Card";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Avatar from "@mui/material/Avatar";
import Tooltip from "@mui/material/Tooltip";
import IconButton from "@mui/material/IconButton";
import Typography from "@mui/material/Typography";
import LinearProgress from "@mui/material/LinearProgress";

import { useTranslate } from "src/locales";

import { Iconify } from "src/components/iconify";

import { ENGAGEMENT_STATUS_OPTIONS } from "src/types/marketplace";

import { DataGrantDialog } from "./data-grant-dialog";
import { AttendanceDialog } from "./attendance-dialog";
import { ResultUploadDialog } from "./result-upload-dialog";

// ----------------------------------------------------------------------

type Props = {
  engagement: IPartnerEngagement;
  onViewDetails: () => void;
  onActivate?: () => void;
  onCancel?: () => void;
  onComplete?: () => void;
  onGrantData?: (scopes: string[]) => Promise<void>;
  showAttendanceButton?: boolean;
};

export function EngagementCard({
  engagement,
  onViewDetails,
  onActivate,
  onCancel,
  onComplete,
  onGrantData,
  showAttendanceButton = true,
}: Props) {
  const { t } = useTranslate("marketplace");
  const dataGrantDialog = useBoolean();
  const attendanceDialog = useBoolean();
  const resultUploadDialog = useBoolean();
  const [isLoading, setIsLoading] = useState(false);

  const statusConfig =
    ENGAGEMENT_STATUS_OPTIONS.find((opt) => opt.value === engagement.status) ||
    ENGAGEMENT_STATUS_OPTIONS[0];

  const handleAction = useCallback(async (action: (() => void) | undefined) => {
    if (!action) return;
    setIsLoading(true);
    try {
      await action();
    } finally {
      setIsLoading(false);
    }
  }, []);

  // Calculate data sharing progress
  const dataProgress =
    engagement.grantedDataScopes.length > 0
      ? Math.round((engagement.grantedDataScopes.length / 3) * 100) // Assuming ~3 scopes on average
      : 0;

  const renderStatusChip = (
    <Chip
      size="small"
      label={statusConfig.label}
      color={statusConfig.color}
      icon={<Iconify icon={statusConfig.icon} width={14} />}
      sx={{ height: 24 }}
    />
  );

  // Show attendance button for active engagements (not draft, not completed/cancelled)
  const canTrackAttendance =
    showAttendanceButton &&
    ["active", "data_shared", "processing", "delivered"].includes(
      engagement.status,
    );

  // Show result upload button when data has been shared or processing
  const canUploadResults = ["data_shared", "processing", "delivered"].includes(
    engagement.status,
  );

  const renderActions = (
    <Stack direction="row" spacing={0.5}>
      {engagement.status === "draft" && onActivate && (
        <Tooltip title="Engagement aktivieren">
          <IconButton
            size="small"
            color="primary"
            onClick={() => handleAction(onActivate)}
            disabled={isLoading}
          >
            <Iconify icon="solar:play-bold" width={18} />
          </IconButton>
        </Tooltip>
      )}
      {engagement.status === "active" && (
        <Tooltip title="Daten freigeben">
          <IconButton
            size="small"
            color="info"
            onClick={dataGrantDialog.onTrue}
          >
            <Iconify icon="solar:share-bold" width={18} />
          </IconButton>
        </Tooltip>
      )}
      {canTrackAttendance && (
        <Tooltip title="Teilnehmer-Tracking">
          <IconButton
            size="small"
            color="secondary"
            onClick={(e) => {
              e.stopPropagation();
              attendanceDialog.onTrue();
            }}
          >
            <Iconify icon="solar:users-group-rounded-bold" width={18} />
          </IconButton>
        </Tooltip>
      )}
      {canUploadResults && (
        <Tooltip title="Ergebnis hochladen">
          <IconButton
            size="small"
            color="primary"
            onClick={(e) => {
              e.stopPropagation();
              resultUploadDialog.onTrue();
            }}
          >
            <Iconify icon="solar:upload-bold" width={18} />
          </IconButton>
        </Tooltip>
      )}
      {engagement.status === "delivered" && onComplete && (
        <Tooltip title="Abschließen">
          <IconButton
            size="small"
            color="success"
            onClick={() => handleAction(onComplete)}
            disabled={isLoading}
          >
            <Iconify icon="solar:check-circle-bold" width={18} />
          </IconButton>
        </Tooltip>
      )}
      {engagement.status !== "completed" &&
        engagement.status !== "cancelled" &&
        onCancel && (
          <Tooltip title="Abbrechen">
            <IconButton
              size="small"
              color="error"
              onClick={() => handleAction(onCancel)}
              disabled={isLoading}
            >
              <Iconify icon="solar:close-circle-bold" width={18} />
            </IconButton>
          </Tooltip>
        )}
    </Stack>
  );

  return (
    <>
      <Card
        sx={{
          p: 2.5,
          cursor: "pointer",
          transition: "all 0.2s",
          "&:hover": {
            boxShadow: (theme) => theme.shadows[8],
            transform: "translateY(-2px)",
          },
        }}
        onClick={onViewDetails}
        data-testid={`engagement-card-${engagement.id}`}
      >
        <Stack spacing={2}>
          {/* Header */}
          <Stack
            direction="row"
            alignItems="flex-start"
            justifyContent="space-between"
          >
            <Stack direction="row" spacing={2} alignItems="center">
              <Avatar
                sx={{
                  width: 48,
                  height: 48,
                  bgcolor: "primary.lighter",
                  color: "primary.main",
                  fontWeight: "bold",
                }}
              >
                {engagement.providerName.charAt(0).toUpperCase()}
              </Avatar>
              <Box>
                <Typography variant="subtitle1" noWrap sx={{ maxWidth: 200 }}>
                  {engagement.offeringTitle}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {engagement.providerName}
                </Typography>
              </Box>
            </Stack>
            {renderStatusChip}
          </Stack>

          {/* Progress (for active engagements) */}
          {["active", "data_shared", "processing"].includes(
            engagement.status,
          ) && (
            <Box>
              <Stack
                direction="row"
                justifyContent="space-between"
                sx={{ mb: 0.5 }}
              >
                <Typography variant="caption" color="text.secondary">
                  Datenfreigabe
                </Typography>
                <Typography variant="caption" color="text.secondary">
                  {engagement.grantedDataScopes.length} Bereiche
                </Typography>
              </Stack>
              <LinearProgress
                variant="determinate"
                value={dataProgress}
                color={dataProgress === 100 ? "success" : "primary"}
                sx={{ height: 6, borderRadius: 1 }}
              />
            </Box>
          )}

          {/* Deliveries (for delivered/completed engagements) */}
          {engagement.deliveredOutputs.length > 0 && (
            <Stack direction="row" spacing={0.5} flexWrap="wrap" useFlexGap>
              {engagement.deliveredOutputs.map((output) => (
                <Chip
                  key={output}
                  size="small"
                  label={output.replace(/_/g, " ")}
                  variant="soft"
                  color="success"
                  icon={<Iconify icon="solar:file-download-bold" width={14} />}
                  sx={{ height: 22, fontSize: "0.7rem" }}
                />
              ))}
            </Stack>
          )}

          {/* Contact & Scheduling */}
          <Stack direction="row" spacing={2} alignItems="center">
            {engagement.partnerContact.name && (
              <Stack direction="row" spacing={0.5} alignItems="center">
                <Iconify
                  icon="solar:user-bold"
                  width={14}
                  color="text.secondary"
                />
                <Typography variant="caption" color="text.secondary">
                  {engagement.partnerContact.name}
                </Typography>
              </Stack>
            )}
            {engagement.scheduledDate && (
              <Stack direction="row" spacing={0.5} alignItems="center">
                <Iconify
                  icon="solar:calendar-bold"
                  width={14}
                  color="text.secondary"
                />
                <Typography variant="caption" color="text.secondary">
                  {new Date(engagement.scheduledDate).toLocaleDateString(
                    "de-DE",
                  )}
                </Typography>
              </Stack>
            )}
          </Stack>

          {/* Actions */}
          <Stack
            direction="row"
            justifyContent="space-between"
            alignItems="center"
            onClick={(e) => e.stopPropagation()}
          >
            <Button size="small" variant="outlined" onClick={onViewDetails}>
              Details
            </Button>
            {renderActions}
          </Stack>
        </Stack>
      </Card>

      {/* Data Grant Dialog */}
      <DataGrantDialog
        open={dataGrantDialog.value}
        onClose={dataGrantDialog.onFalse}
        engagement={engagement}
        onGrant={onGrantData}
      />

      {/* Attendance Tracking Dialog */}
      {canTrackAttendance && (
        <AttendanceDialog
          open={attendanceDialog.value}
          onClose={attendanceDialog.onFalse}
          engagement={engagement}
        />
      )}

      {/* Result Upload Dialog */}
      {canUploadResults && (
        <ResultUploadDialog
          open={resultUploadDialog.value}
          onClose={resultUploadDialog.onFalse}
          engagement={engagement}
        />
      )}
    </>
  );
}

