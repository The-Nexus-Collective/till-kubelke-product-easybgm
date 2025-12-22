import type {
  TParticipationStatus,
  IInterventionParticipation,
} from "src/types/marketplace";

import { useState, useCallback } from "react";

import Box from "@mui/material/Box";
import Card from "@mui/material/Card";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Table from "@mui/material/Table";
import Button from "@mui/material/Button";
import Tooltip from "@mui/material/Tooltip";
import Checkbox from "@mui/material/Checkbox";
import TableRow from "@mui/material/TableRow";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TableHead from "@mui/material/TableHead";
import IconButton from "@mui/material/IconButton";
import Typography from "@mui/material/Typography";
import LinearProgress from "@mui/material/LinearProgress";
import TableContainer from "@mui/material/TableContainer";

import { Iconify } from "src/components/iconify";

import { PARTICIPATION_STATUS_OPTIONS } from "src/types/marketplace";

// ----------------------------------------------------------------------

type Props = {
  participations: IInterventionParticipation[];
  isLoading?: boolean;
  onMarkAttended: (id: number) => Promise<void>;
  onMarkNoShow: (id: number) => Promise<void>;
  onMarkCancelled: (id: number) => Promise<void>;
  onBulkMarkAttended?: (ids: number[]) => Promise<void>;
  showEmployeeDetails?: boolean;
};

export function AttendanceTracker({
  participations,
  isLoading = false,
  onMarkAttended,
  onMarkNoShow,
  onMarkCancelled,
  onBulkMarkAttended,
  showEmployeeDetails = true,
}: Props) {
  const [selected, setSelected] = useState<number[]>([]);
  const [loadingIds, setLoadingIds] = useState<number[]>([]);

  // Calculate stats
  const totalCount = participations.length;
  const attendedCount = participations.filter(
    (p) => p.status === "attended",
  ).length;
  const noShowCount = participations.filter(
    (p) => p.status === "no_show",
  ).length;
  const cancelledCount = participations.filter(
    (p) => p.status === "cancelled",
  ).length;
  const pendingCount = participations.filter(
    (p) => p.status === "registered",
  ).length;
  const attendanceRate =
    totalCount > 0 ? Math.round((attendedCount / totalCount) * 100) : 0;

  const handleSelectAll = useCallback(
    (event: React.ChangeEvent<HTMLInputElement>) => {
      if (event.target.checked) {
        const pendingIds = participations
          .filter((p) => p.status === "registered")
          .map((p) => p.id);
        setSelected(pendingIds);
      } else {
        setSelected([]);
      }
    },
    [participations],
  );

  const handleSelect = useCallback((id: number) => {
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((i) => i !== id) : [...prev, id],
    );
  }, []);

  const handleAction = useCallback(
    async (id: number, action: (id: number) => Promise<void>) => {
      setLoadingIds((prev) => [...prev, id]);
      try {
        await action(id);
      } finally {
        setLoadingIds((prev) => prev.filter((i) => i !== id));
        setSelected((prev) => prev.filter((i) => i !== id));
      }
    },
    [],
  );

  const handleBulkAttended = useCallback(async () => {
    if (onBulkMarkAttended && selected.length > 0) {
      setLoadingIds((prev) => [...prev, ...selected]);
      try {
        await onBulkMarkAttended(selected);
        setSelected([]);
      } finally {
        setLoadingIds((prev) => prev.filter((id) => !selected.includes(id)));
      }
    } else {
      // Fallback: mark one by one
      for (const id of selected) {
        await handleAction(id, onMarkAttended);
      }
    }
  }, [selected, onBulkMarkAttended, onMarkAttended, handleAction]);

  const getStatusChip = (status: TParticipationStatus) => {
    const config = PARTICIPATION_STATUS_OPTIONS.find(
      (opt) => opt.value === status,
    );
    if (!config) return null;

    return (
      <Chip
        size="small"
        label={config.label}
        color={config.color}
        icon={<Iconify icon={config.icon} width={14} />}
        sx={{ height: 24 }}
      />
    );
  };

  const renderActions = (participation: IInterventionParticipation) => {
    const isActionLoading = loadingIds.includes(participation.id);

    if (participation.status !== "registered") {
      return getStatusChip(participation.status);
    }

    return (
      <Stack direction="row" spacing={0.5}>
        <Tooltip title="Als teilgenommen markieren">
          <IconButton
            size="small"
            color="success"
            onClick={(e) => {
              e.stopPropagation();
              handleAction(participation.id, onMarkAttended);
            }}
            disabled={isActionLoading}
          >
            <Iconify icon="solar:check-circle-bold" width={20} />
          </IconButton>
        </Tooltip>
        <Tooltip title="Als nicht erschienen markieren">
          <IconButton
            size="small"
            color="warning"
            onClick={(e) => {
              e.stopPropagation();
              handleAction(participation.id, onMarkNoShow);
            }}
            disabled={isActionLoading}
          >
            <Iconify icon="solar:close-circle-bold" width={20} />
          </IconButton>
        </Tooltip>
        <Tooltip title="Absagen">
          <IconButton
            size="small"
            color="error"
            onClick={(e) => {
              e.stopPropagation();
              handleAction(participation.id, onMarkCancelled);
            }}
            disabled={isActionLoading}
          >
            <Iconify icon="solar:forbidden-circle-bold" width={20} />
          </IconButton>
        </Tooltip>
      </Stack>
    );
  };

  return (
    <Card>
      {/* Summary Stats */}
      <Box sx={{ p: 2, borderBottom: 1, borderColor: "divider" }}>
        <Stack
          direction="row"
          spacing={3}
          alignItems="center"
          justifyContent="space-between"
        >
          <Stack direction="row" spacing={2} alignItems="center">
            <Stack spacing={0.5}>
              <Typography variant="h4">{attendanceRate}%</Typography>
              <Typography variant="caption" color="text.secondary">
                Teilnahmequote
              </Typography>
            </Stack>
            <LinearProgress
              variant="determinate"
              value={attendanceRate}
              sx={{ width: 100, height: 8, borderRadius: 1 }}
              color={
                attendanceRate >= 80
                  ? "success"
                  : attendanceRate >= 50
                    ? "warning"
                    : "error"
              }
            />
          </Stack>

          <Stack direction="row" spacing={2}>
            <Chip
              size="small"
              label={`${attendedCount} teilgenommen`}
              color="success"
              variant="soft"
            />
            <Chip
              size="small"
              label={`${noShowCount} nicht erschienen`}
              color="warning"
              variant="soft"
            />
            <Chip
              size="small"
              label={`${cancelledCount} abgesagt`}
              color="error"
              variant="soft"
            />
            <Chip
              size="small"
              label={`${pendingCount} ausstehend`}
              color="default"
              variant="soft"
            />
          </Stack>
        </Stack>
      </Box>

      {/* Bulk Actions */}
      {pendingCount > 0 && (
        <Box sx={{ px: 2, py: 1, bgcolor: "action.hover" }}>
          <Stack direction="row" spacing={2} alignItems="center">
            <Checkbox
              indeterminate={
                selected.length > 0 && selected.length < pendingCount
              }
              checked={selected.length === pendingCount && pendingCount > 0}
              onChange={handleSelectAll}
            />
            <Typography variant="body2" color="text.secondary">
              {selected.length > 0
                ? `${selected.length} ausgewählt`
                : "Ausstehende auswählen"}
            </Typography>
            {selected.length > 0 && (
              <Button
                size="small"
                variant="contained"
                color="success"
                startIcon={<Iconify icon="solar:check-circle-bold" />}
                onClick={handleBulkAttended}
                disabled={loadingIds.length > 0}
              >
                Alle als teilgenommen markieren
              </Button>
            )}
          </Stack>
        </Box>
      )}

      {/* Participants Table */}
      <TableContainer>
        <Table size="small">
          <TableHead>
            <TableRow>
              {pendingCount > 0 && <TableCell padding="checkbox" />}
              {showEmployeeDetails && (
                <>
                  <TableCell>Name</TableCell>
                  <TableCell>E-Mail</TableCell>
                  <TableCell>Abteilung</TableCell>
                </>
              )}
              {!showEmployeeDetails && <TableCell>Teilnehmer</TableCell>}
              <TableCell>Datum</TableCell>
              <TableCell>Besonderheiten</TableCell>
              <TableCell align="right">Status / Aktionen</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell
                  colSpan={showEmployeeDetails ? 7 : 5}
                  align="center"
                  sx={{ py: 4 }}
                >
                  <Typography color="text.secondary">Lädt...</Typography>
                </TableCell>
              </TableRow>
            ) : participations.length === 0 ? (
              <TableRow>
                <TableCell
                  colSpan={showEmployeeDetails ? 7 : 5}
                  align="center"
                  sx={{ py: 4 }}
                >
                  <Stack alignItems="center" spacing={1}>
                    <Iconify
                      icon="solar:users-group-rounded-bold"
                      width={48}
                      color="text.disabled"
                    />
                    <Typography color="text.secondary">
                      Noch keine Teilnehmer registriert
                    </Typography>
                  </Stack>
                </TableCell>
              </TableRow>
            ) : (
              participations.map((participation) => (
                <TableRow
                  key={participation.id}
                  hover
                  sx={{
                    opacity: loadingIds.includes(participation.id) ? 0.5 : 1,
                  }}
                >
                  {pendingCount > 0 && (
                    <TableCell padding="checkbox">
                      {participation.status === "registered" && (
                        <Checkbox
                          checked={selected.includes(participation.id)}
                          onChange={() => handleSelect(participation.id)}
                        />
                      )}
                    </TableCell>
                  )}
                  {showEmployeeDetails ? (
                    <>
                      <TableCell>
                        <Typography variant="body2" fontWeight={500}>
                          {participation.employeeName || "–"}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2" color="text.secondary">
                          {participation.employeeEmail || "–"}
                        </Typography>
                      </TableCell>
                      <TableCell>
                        <Typography variant="body2" color="text.secondary">
                          {participation.department || "–"}
                        </Typography>
                      </TableCell>
                    </>
                  ) : (
                    <TableCell>
                      <Typography variant="body2">
                        Teilnehmer #{participation.id}
                      </Typography>
                    </TableCell>
                  )}
                  <TableCell>
                    <Typography variant="body2">
                      {participation.eventDate
                        ? new Date(participation.eventDate).toLocaleDateString(
                            "de-DE",
                          )
                        : "–"}
                    </Typography>
                  </TableCell>
                  <TableCell>
                    {participation.specialRequirements &&
                    participation.specialRequirements.length > 0 ? (
                      <Stack
                        direction="row"
                        spacing={0.5}
                        flexWrap="wrap"
                        useFlexGap
                      >
                        {participation.specialRequirements.map((req) => (
                          <Chip
                            key={req}
                            size="small"
                            label={req}
                            variant="outlined"
                            sx={{ height: 20, fontSize: "0.7rem" }}
                          />
                        ))}
                      </Stack>
                    ) : (
                      <Typography variant="body2" color="text.disabled">
                        –
                      </Typography>
                    )}
                  </TableCell>
                  <TableCell align="right">
                    {renderActions(participation)}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </TableContainer>

      {/* Footer */}
      <Box
        sx={{
          p: 2,
          borderTop: 1,
          borderColor: "divider",
          bgcolor: "background.neutral",
        }}
      >
        <Stack
          direction="row"
          justifyContent="space-between"
          alignItems="center"
        >
          <Typography variant="caption" color="text.secondary">
            Gesamt: {totalCount} Teilnehmer
          </Typography>
          <Typography variant="caption" color="text.secondary">
            {attendedCount} von {totalCount - cancelledCount} erschienen
          </Typography>
        </Stack>
      </Box>
    </Card>
  );
}

