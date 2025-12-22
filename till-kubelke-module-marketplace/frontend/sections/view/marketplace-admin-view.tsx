import type { IServiceProviderDetails } from "src/types/marketplace";

import { useState, useCallback } from "react";
import { useBoolean } from "minimal-shared/hooks";

import Box from "@mui/material/Box";
import Tab from "@mui/material/Tab";
import Tabs from "@mui/material/Tabs";
import Card from "@mui/material/Card";
import Chip from "@mui/material/Chip";
import Stack from "@mui/material/Stack";
import Table from "@mui/material/Table";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import Tooltip from "@mui/material/Tooltip";
import TableRow from "@mui/material/TableRow";
import TextField from "@mui/material/TextField";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import IconButton from "@mui/material/IconButton";
import Typography from "@mui/material/Typography";
import LoadingButton from "@mui/lab/LoadingButton";
import DialogTitle from "@mui/material/DialogTitle";
import DialogContent from "@mui/material/DialogContent";
import DialogActions from "@mui/material/DialogActions";
import TableContainer from "@mui/material/TableContainer";
import TablePagination from "@mui/material/TablePagination";
import CircularProgress from "@mui/material/CircularProgress";

import { paths } from "src/routes/paths";

import { useTranslate } from "src/locales";
import { DashboardContent } from "src/layouts/dashboard";
import {
  rejectProvider,
  deleteProvider,
  approveProvider,
  useGetAdminProviders,
  useGetMarketplaceAdminStats,
} from "src/actions/marketplace";

import { Label } from "src/components/label";
import { toast } from "src/components/snackbar";
import { Iconify } from "src/components/iconify";
import { Scrollbar } from "src/components/scrollbar";
import { ConfirmDialog } from "src/components/custom-dialog";
import { CrudViewHeader } from "src/components/crud-view-header";
import { TableNoData, TableHeadCustom } from "src/components/table";
import { CustomBreadcrumbs } from "src/components/custom-breadcrumbs";

import { ProviderRegistrationForm } from "src/sections/marketplace/provider-registration-form";

import { PROVIDER_STATUS_OPTIONS } from "src/types/marketplace";

// ----------------------------------------------------------------------

// TABLE_HEAD wird dynamisch mit t() erstellt

// ----------------------------------------------------------------------

export function MarketplaceAdminView() {
  const { t } = useTranslate("marketplace");
  const [currentTab, setCurrentTab] = useState<
    "pending" | "approved" | "rejected"
  >("approved");
  const [page, setPage] = useState(0);
  const [rowsPerPage, setRowsPerPage] = useState(20);

  // Data - now uses status filter from current tab!
  const { stats, statsMutate } = useGetMarketplaceAdminStats();
  const { providers, pagination, providersLoading, providersMutate } =
    useGetAdminProviders({
      status: currentTab,
      page: page + 1,
      limit: rowsPerPage,
    });

  const TABLE_HEAD = [
    { id: "companyName", label: t("admin.table.company"), width: 280 },
    { id: "contactEmail", label: t("admin.table.email"), width: 220 },
    { id: "categories", label: t("admin.table.categories"), width: 200 },
    { id: "status", label: t("admin.table.status"), width: 120 },
    { id: "createdAt", label: t("admin.table.created"), width: 140 },
    { id: "", width: 88 },
  ];

  // Dialogs
  const detailDialog = useBoolean();
  const rejectDialog = useBoolean();
  const deleteDialog = useBoolean();
  const createDialog = useBoolean();
  const editDialog = useBoolean();
  const [selectedProvider, setSelectedProvider] =
    useState<IServiceProviderDetails | null>(null);
  const [rejectReason, setRejectReason] = useState("");
  const [actionLoading, setActionLoading] = useState(false);

  // Handlers
  const handleTabChange = useCallback(
    (
      _: React.SyntheticEvent,
      newValue: "pending" | "approved" | "rejected",
    ) => {
      setCurrentTab(newValue);
      setPage(0);
    },
    [],
  );

  const handleViewDetails = useCallback(
    (provider: IServiceProviderDetails) => {
      setSelectedProvider(provider);
      detailDialog.onTrue();
    },
    [detailDialog],
  );

  const handleEdit = useCallback(
    (provider: IServiceProviderDetails) => {
      setSelectedProvider(provider);
      editDialog.onTrue();
    },
    [editDialog],
  );

  const handleEditSuccess = useCallback(async () => {
    editDialog.onFalse();
    providersMutate();
    statsMutate();
    toast.success(t("admin.messages.updated"));
  }, [editDialog, providersMutate, statsMutate, t]);

  const handleApprove = useCallback(
    async (provider: IServiceProviderDetails) => {
      setActionLoading(true);
      try {
        await approveProvider(provider.id);
        toast.success(
          t("admin.messages.approved", { companyName: provider.companyName }),
        );
        providersMutate();
        statsMutate();
      } catch (error: any) {
        const errorMessage =
          error?.response?.data?.error ||
          error?.message ||
          t("admin.messages.errorApprove");
        toast.error(errorMessage);
      } finally {
        setActionLoading(false);
      }
    },
    [providersMutate, statsMutate, t],
  );

  const handleOpenReject = useCallback(
    (provider: IServiceProviderDetails) => {
      setSelectedProvider(provider);
      setRejectReason("");
      rejectDialog.onTrue();
    },
    [rejectDialog],
  );

  const handleConfirmReject = useCallback(async () => {
    if (!selectedProvider || !rejectReason.trim()) return;

    setActionLoading(true);
    try {
      await rejectProvider(selectedProvider.id, rejectReason);
      toast.success(
        t("admin.messages.rejected", {
          companyName: selectedProvider.companyName,
        }),
      );
      rejectDialog.onFalse();
      providersMutate();
      statsMutate();
    } catch (error: any) {
      const errorMessage =
        error?.response?.data?.error ||
        error?.message ||
        t("admin.messages.errorReject");
      toast.error(errorMessage);
    } finally {
      setActionLoading(false);
    }
  }, [
    selectedProvider,
    rejectReason,
    rejectDialog,
    providersMutate,
    statsMutate,
    t,
  ]);

  const handleOpenDelete = useCallback(
    (provider: IServiceProviderDetails) => {
      setSelectedProvider(provider);
      deleteDialog.onTrue();
    },
    [deleteDialog],
  );

  const handleConfirmDelete = useCallback(async () => {
    if (!selectedProvider) return;

    setActionLoading(true);
    try {
      await deleteProvider(selectedProvider.id);
      toast.success(
        t("admin.messages.deleted", {
          companyName: selectedProvider.companyName,
        }),
      );
      deleteDialog.onFalse();
      providersMutate();
      statsMutate();
    } catch (error: any) {
      const errorMessage =
        error?.response?.data?.error ||
        error?.message ||
        t("admin.messages.errorDelete");
      toast.error(errorMessage);
    } finally {
      setActionLoading(false);
    }
  }, [selectedProvider, deleteDialog, providersMutate, statsMutate, t]);

  const handleChangePage = useCallback((_: unknown, newPage: number) => {
    setPage(newPage);
  }, []);

  const handleChangeRowsPerPage = useCallback(
    (event: React.ChangeEvent<HTMLInputElement>) => {
      setRowsPerPage(parseInt(event.target.value, 10));
      setPage(0);
    },
    [],
  );

  const handleCreateSuccess = useCallback(async () => {
    createDialog.onFalse();
    providersMutate();
    statsMutate();
  }, [createDialog, providersMutate, statsMutate]);

  const TABS = [
    {
      value: "pending",
      label: t("admin.tabs.pending"),
      count: stats.pending,
      color: "warning" as const,
    },
    {
      value: "approved",
      label: t("admin.tabs.approved"),
      count: stats.approved,
      color: "success" as const,
    },
    {
      value: "rejected",
      label: t("admin.tabs.rejected"),
      count: stats.rejected,
      color: "error" as const,
    },
  ];

  return (
    <>
      <DashboardContent>
        {/* Header with admin variant for super-admin area */}
        <CrudViewHeader
          title={t("admin.title")}
          subtitle={t("admin.subtitle")}
          icon="solar:shop-2-bold"
          variant="admin"
          gradient="admin-orange"
          stats={[
            { value: stats.total, label: t("admin.stats.total") },
            { value: stats.pending, label: t("admin.stats.pending") },
            { value: stats.approved, label: t("admin.stats.approved") },
            { value: stats.rejected, label: t("admin.stats.rejected") },
          ]}
          infoTitle={t("admin.info.title")}
          infoDescription={t("admin.info.description")}
          storageKey="marketplace-admin-header-info"
        />

        <CustomBreadcrumbs
          links={[
            { name: "Dashboard", href: paths.dashboard.root },
            { name: "System", href: paths.dashboard.system.root },
            { name: "Marktplatz" },
          ]}
          action={
            <Button
              variant="contained"
              startIcon={<Iconify icon="mingcute:add-line" />}
              onClick={createDialog.onTrue}
            >
              {t("admin.create")}
            </Button>
          }
          sx={{ mb: { xs: 3, md: 3 } }}
        />

        <Card>
          {/* Tabs */}
          <Tabs
            value={currentTab}
            onChange={handleTabChange}
            sx={{ px: 2.5, pt: 2 }}
          >
            {TABS.map((tab) => (
              <Tab
                key={tab.value}
                value={tab.value}
                label={tab.label}
                icon={
                  <Chip
                    size="small"
                    label={tab.count}
                    color={tab.color}
                    variant="soft"
                    sx={{ ml: 1 }}
                  />
                }
                iconPosition="end"
              />
            ))}
          </Tabs>

          {/* Table */}
          {providersLoading ? (
            <Box sx={{ py: 6, textAlign: "center" }}>
              <CircularProgress />
            </Box>
          ) : (
            <>
              <TableContainer sx={{ overflow: "unset" }}>
                <Scrollbar>
                  <Table sx={{ minWidth: { xs: 650, md: 960 } }}>
                    <TableHeadCustom headCells={TABLE_HEAD} />

                    <TableBody>
                      {providers.length === 0 ? (
                        <TableNoData notFound />
                      ) : (
                        providers.map((provider) => (
                          <ProviderTableRow
                            key={provider.id}
                            provider={provider}
                            onView={() => handleViewDetails(provider)}
                            onEdit={() => handleEdit(provider)}
                            onApprove={() => handleApprove(provider)}
                            onReject={() => handleOpenReject(provider)}
                            onDelete={() => handleOpenDelete(provider)}
                            actionLoading={actionLoading}
                          />
                        ))
                      )}
                    </TableBody>
                  </Table>
                </Scrollbar>
              </TableContainer>

              <TablePagination
                component="div"
                count={pagination.total}
                page={page}
                rowsPerPage={rowsPerPage}
                onPageChange={handleChangePage}
                onRowsPerPageChange={handleChangeRowsPerPage}
                rowsPerPageOptions={[10, 20, 50]}
                labelRowsPerPage="Pro Seite:"
                labelDisplayedRows={({ from, to, count }) =>
                  `${from}–${to} von ${count}`
                }
              />
            </>
          )}
        </Card>
      </DashboardContent>

      {/* Provider Detail Dialog */}
      <Dialog
        open={detailDialog.value}
        onClose={detailDialog.onFalse}
        maxWidth="md"
        fullWidth
      >
        <DialogTitle>{selectedProvider?.companyName}</DialogTitle>
        <DialogContent dividers>
          {selectedProvider && (
            <Stack spacing={2}>
              <Box>
                <Typography variant="subtitle2">
                  {t("admin.dialogs.details.description")}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {selectedProvider.description}
                </Typography>
              </Box>
              <Box>
                <Typography variant="subtitle2">
                  {t("admin.dialogs.details.contact")}
                </Typography>
                <Typography variant="body2">
                  {selectedProvider.contactEmail}
                </Typography>
                {selectedProvider.contactPhone && (
                  <Typography variant="body2">
                    {selectedProvider.contactPhone}
                  </Typography>
                )}
                {selectedProvider.contactPerson && (
                  <Typography variant="body2">
                    {selectedProvider.contactPerson}
                  </Typography>
                )}
              </Box>
              {selectedProvider.website && (
                <Box>
                  <Typography variant="subtitle2">
                    {t("admin.dialogs.details.website")}
                  </Typography>
                  <Typography
                    variant="body2"
                    component="a"
                    href={selectedProvider.website}
                    target="_blank"
                    rel="noopener noreferrer"
                    sx={{ color: "primary.main" }}
                  >
                    {selectedProvider.website}
                  </Typography>
                </Box>
              )}
              <Box>
                <Typography variant="subtitle2">
                  {t("admin.dialogs.details.categories")}
                </Typography>
                <Stack
                  direction="row"
                  spacing={0.5}
                  flexWrap="wrap"
                  useFlexGap
                  sx={{ mt: 0.5 }}
                >
                  {selectedProvider.categories.map((cat) => (
                    <Chip
                      key={cat.id}
                      label={cat.name}
                      size="small"
                      color="primary"
                      variant="soft"
                    />
                  ))}
                </Stack>
              </Box>
              <Box>
                <Typography variant="subtitle2">
                  {t("admin.dialogs.details.tags")}
                </Typography>
                <Stack
                  direction="row"
                  spacing={0.5}
                  flexWrap="wrap"
                  useFlexGap
                  sx={{ mt: 0.5 }}
                >
                  {selectedProvider.tags.map((tag) => (
                    <Chip
                      key={tag.id}
                      label={tag.name}
                      size="small"
                      variant="outlined"
                    />
                  ))}
                </Stack>
              </Box>
            </Stack>
          )}
        </DialogContent>
        <DialogActions sx={{ px: 3, py: 2 }}>
          <Button onClick={detailDialog.onFalse}>
            {t("admin.actions.close")}
          </Button>
          {selectedProvider?.status === "pending" && (
            <>
              <Button
                color="error"
                onClick={() => {
                  detailDialog.onFalse();
                  handleOpenReject(selectedProvider);
                }}
              >
                {t("admin.actions.reject")}
              </Button>
              <LoadingButton
                variant="contained"
                color="success"
                loading={actionLoading}
                onClick={() => handleApprove(selectedProvider)}
              >
                {t("admin.actions.approve")}
              </LoadingButton>
            </>
          )}
        </DialogActions>
      </Dialog>

      {/* Reject Dialog */}
      <Dialog
        open={rejectDialog.value}
        onClose={rejectDialog.onFalse}
        maxWidth="sm"
        fullWidth
      >
        <DialogTitle>{t("admin.dialogs.reject.title")}</DialogTitle>
        <DialogContent>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
            {t("admin.dialogs.reject.description", {
              companyName: selectedProvider?.companyName,
            })}
          </Typography>
          <TextField
            fullWidth
            multiline
            rows={3}
            label={t("admin.dialogs.reject.reasonLabel")}
            value={rejectReason}
            onChange={(e) => setRejectReason(e.target.value)}
            placeholder={t("admin.dialogs.reject.reasonPlaceholder")}
          />
        </DialogContent>
        <DialogActions sx={{ px: 3, py: 2 }}>
          <Button onClick={rejectDialog.onFalse}>
            {t("admin.dialogs.reject.cancel")}
          </Button>
          <LoadingButton
            variant="contained"
            color="error"
            loading={actionLoading}
            disabled={!rejectReason.trim()}
            onClick={handleConfirmReject}
          >
            {t("admin.dialogs.reject.confirm")}
          </LoadingButton>
        </DialogActions>
      </Dialog>

      {/* Delete Confirm Dialog */}
      <ConfirmDialog
        open={deleteDialog.value}
        onClose={deleteDialog.onFalse}
        title={t("admin.dialogs.delete.title")}
        content={t("admin.dialogs.delete.content", {
          companyName: selectedProvider?.companyName,
        })}
        action={
          <LoadingButton
            variant="contained"
            color="error"
            loading={actionLoading}
            onClick={handleConfirmDelete}
          >
            {t("admin.dialogs.delete.confirm")}
          </LoadingButton>
        }
      />

      {/* Create Provider Dialog */}
      <Dialog
        open={createDialog.value}
        onClose={createDialog.onFalse}
        maxWidth="lg"
        fullWidth
        PaperProps={{
          sx: {
            maxHeight: "90vh",
            display: "flex",
            flexDirection: "column",
          },
        }}
      >
        <DialogTitle sx={{ pb: 2 }}>{t("admin.createDialogTitle")}</DialogTitle>
        <DialogContent
          dividers
          sx={{
            p: 3,
            overflow: "auto",
            flex: 1,
            "&::-webkit-scrollbar": {
              width: "8px",
            },
            "&::-webkit-scrollbar-track": {
              background: "transparent",
            },
            "&::-webkit-scrollbar-thumb": {
              background: (theme) => theme.palette.grey[300],
              borderRadius: "4px",
              "&:hover": {
                background: (theme) => theme.palette.grey[400],
              },
            },
          }}
        >
          <ProviderRegistrationForm
            mode="admin-create"
            onSuccess={handleCreateSuccess}
          />
        </DialogContent>
      </Dialog>

      {/* Edit Provider Dialog */}
      <Dialog
        open={editDialog.value}
        onClose={editDialog.onFalse}
        maxWidth="lg"
        fullWidth
        PaperProps={{
          sx: {
            maxHeight: "90vh",
            display: "flex",
            flexDirection: "column",
          },
        }}
      >
        <DialogTitle sx={{ pb: 2 }}>
          {t("admin.editDialogTitle", {
            companyName: selectedProvider?.companyName,
          })}
        </DialogTitle>
        <DialogContent
          dividers
          sx={{
            p: 3,
            overflow: "auto",
            flex: 1,
            "&::-webkit-scrollbar": {
              width: "8px",
            },
            "&::-webkit-scrollbar-track": {
              background: "transparent",
            },
            "&::-webkit-scrollbar-thumb": {
              background: (theme) => theme.palette.grey[300],
              borderRadius: "4px",
              "&:hover": {
                background: (theme) => theme.palette.grey[400],
              },
            },
          }}
        >
          {selectedProvider && (
            <ProviderRegistrationForm
              mode="admin-edit"
              providerId={selectedProvider.id}
              initialData={selectedProvider as any}
              onSuccess={handleEditSuccess}
            />
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}

// ----------------------------------------------------------------------

type ProviderTableRowProps = {
  provider: IServiceProviderDetails;
  onView: () => void;
  onEdit: () => void;
  onApprove: () => void;
  onReject: () => void;
  onDelete: () => void;
  actionLoading: boolean;
};

function ProviderTableRow({
  provider,
  onView,
  onEdit,
  onApprove,
  onReject,
  onDelete,
  actionLoading,
}: ProviderTableRowProps) {
  const { t } = useTranslate("marketplace");
  const statusOption = PROVIDER_STATUS_OPTIONS.find(
    (s) => s.value === provider.status,
  );

  return (
    <TableRow hover>
      <TableCell>
        <Stack direction="row" alignItems="center" spacing={2}>
          <Box>
            <Typography variant="subtitle2" noWrap>
              {provider.companyName}
            </Typography>
            {provider.shortDescription && (
              <Typography
                variant="caption"
                color="text.secondary"
                noWrap
                sx={{ maxWidth: 240, display: "block" }}
              >
                {provider.shortDescription}
              </Typography>
            )}
          </Box>
        </Stack>
      </TableCell>

      <TableCell>
        <Typography variant="body2" noWrap>
          {provider.contactEmail}
        </Typography>
      </TableCell>

      <TableCell>
        <Stack direction="row" spacing={0.5} flexWrap="wrap" useFlexGap>
          {provider.categories.slice(0, 2).map((cat) => (
            <Chip key={cat.id} label={cat.name} size="small" variant="soft" />
          ))}
          {provider.categories.length > 2 && (
            <Chip label={`+${provider.categories.length - 2}`} size="small" />
          )}
        </Stack>
      </TableCell>

      <TableCell>
        <Label color={statusOption?.color || "default"}>
          {statusOption?.label || provider.status}
        </Label>
      </TableCell>

      <TableCell>
        <Typography variant="body2">
          {new Date(provider.createdAt).toLocaleDateString("de-DE")}
        </Typography>
      </TableCell>

      <TableCell align="right">
        <Stack direction="row" spacing={0.5}>
          <Tooltip title={t("admin.actions.view")}>
            <IconButton size="small" onClick={onView}>
              <Iconify icon="solar:eye-bold" width={18} />
            </IconButton>
          </Tooltip>

          <Tooltip title={t("admin.actions.edit")}>
            <IconButton size="small" color="primary" onClick={onEdit}>
              <Iconify icon="solar:pen-bold" width={18} />
            </IconButton>
          </Tooltip>

          {provider.status === "pending" && (
            <>
              <Tooltip title={t("admin.actions.approve")}>
                <IconButton
                  size="small"
                  color="success"
                  onClick={onApprove}
                  disabled={actionLoading}
                >
                  <Iconify icon="solar:check-circle-bold" width={18} />
                </IconButton>
              </Tooltip>
              <Tooltip title={t("admin.actions.reject")}>
                <IconButton
                  size="small"
                  color="error"
                  onClick={onReject}
                  disabled={actionLoading}
                >
                  <Iconify icon="solar:close-circle-bold" width={18} />
                </IconButton>
              </Tooltip>
            </>
          )}

          <Tooltip title={t("admin.actions.delete")}>
            <IconButton
              size="small"
              color="error"
              onClick={onDelete}
              disabled={actionLoading}
            >
              <Iconify icon="solar:trash-bin-trash-bold" width={18} />
            </IconButton>
          </Tooltip>
        </Stack>
      </TableCell>
    </TableRow>
  );
}
