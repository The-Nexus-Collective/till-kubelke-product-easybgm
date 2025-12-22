import type { IMarket } from "src/types/market";

import { useSWRConfig } from "swr";
import { useState, useCallback } from "react";
import { useBoolean, usePopover } from "minimal-shared/hooks";

import Card from "@mui/material/Card";
import Table from "@mui/material/Table";
import Stack from "@mui/material/Stack";
import Button from "@mui/material/Button";
import Dialog from "@mui/material/Dialog";
import MenuItem from "@mui/material/MenuItem";
import TableRow from "@mui/material/TableRow";
import TableBody from "@mui/material/TableBody";
import TableCell from "@mui/material/TableCell";
import TextField from "@mui/material/TextField";
import Typography from "@mui/material/Typography";
import IconButton from "@mui/material/IconButton";
import DialogTitle from "@mui/material/DialogTitle";
import DialogActions from "@mui/material/DialogActions";
import DialogContent from "@mui/material/DialogContent";
import TableContainer from "@mui/material/TableContainer";

import { paths } from "src/routes/paths";

import { useTranslate } from "src/locales";
import axios, { endpoints } from "src/lib/axios";
import { DashboardContent } from "src/layouts/dashboard";
import { useGetMarkets, useGetMarketStats } from "src/actions/market";

import { Label } from "src/components/label";
import { toast } from "src/components/snackbar";
import { Iconify } from "src/components/iconify";
import { Scrollbar } from "src/components/scrollbar";
import { ConfirmDialog } from "src/components/custom-dialog";
import { CustomPopover } from "src/components/custom-popover";
import { CrudViewHeader } from "src/components/crud-view-header";
import { TableNoData, TableHeadCustom } from "src/components/table";
import { CustomBreadcrumbs } from "src/components/custom-breadcrumbs";

// ----------------------------------------------------------------------

const TABLE_HEAD = [
  { id: "code", label: "Code", width: 80 },
  { id: "name", label: "Name", width: 200 },
  { id: "localName", label: "Lokaler Name", width: 180 },
  { id: "currency", label: "Währung", width: 100 },
  { id: "locale", label: "Locale", width: 100 },
  { id: "status", label: "Status", width: 100 },
  { id: "", width: 44 },
];

// ----------------------------------------------------------------------

export function MarketListView() {
  const { t } = useTranslate("messages");
  const { mutate } = useSWRConfig();
  const createDialog = useBoolean();

  const { markets, marketsLoading, marketsEmpty, marketsMutate } =
    useGetMarkets();
  const { stats, statsMutate } = useGetMarketStats();

  const handleDeleteRow = useCallback(
    async (code: string) => {
      try {
        await axios.delete(endpoints.system.markets.delete(code));
        mutate(endpoints.system.markets.list);
        mutate(endpoints.system.markets.stats);
        toast.success(t("success.deleted"));
      } catch (error: any) {
        const errorMessage =
          error?.response?.data?.error ||
          error?.message ||
          t("errors.deleting");
        toast.error(errorMessage);
      }
    },
    [mutate],
  );

  const handleCreateSuccess = useCallback(() => {
    createDialog.onFalse();
    marketsMutate();
    statsMutate();
  }, [createDialog, marketsMutate, statsMutate]);

  const notFound = marketsEmpty || (!marketsLoading && markets.length === 0);

  return (
    <>
      <DashboardContent>
        <CrudViewHeader
          title="Märkte (Länder)"
          subtitle="Verfügbare Länder und Regionen verwalten"
          icon="mingcute:earth-2-fill"
          variant="admin"
          gradient="admin-teal"
          stats={[
            { value: stats.total, label: "Gesamt" },
            { value: stats.active, label: "Aktiv" },
            { value: stats.inactive, label: "Inaktiv" },
          ]}
          infoTitle="Länder-Konfiguration"
          infoDescription="Definieren Sie hier die verfügbaren Märkte/Länder für die Plattform. Jeder Markt kann eigene Währungen und Lokalisierungen haben."
          storageKey="market-list-header-info"
        />

        <CustomBreadcrumbs
          links={[
            { name: "Dashboard", href: paths.dashboard.root },
            { name: "System", href: paths.dashboard.system.root },
            { name: "Märkte" },
          ]}
          action={
            <Button
              variant="contained"
              startIcon={<Iconify icon="mingcute:add-line" />}
              onClick={createDialog.onTrue}
            >
              Markt hinzufügen
            </Button>
          }
          sx={{ mb: 3 }}
        />

        <Card>
          <TableContainer sx={{ overflow: "unset" }}>
            <Scrollbar>
              <Table size="medium" sx={{ minWidth: 800 }}>
                <TableHeadCustom headCells={TABLE_HEAD} />

                <TableBody>
                  {marketsLoading ? (
                    <TableRow>
                      <TableCell
                        colSpan={TABLE_HEAD.length}
                        align="center"
                        sx={{ py: 6 }}
                      >
                        <Typography variant="body2" color="text.secondary">
                          Lade Märkte...
                        </Typography>
                      </TableCell>
                    </TableRow>
                  ) : (
                    <>
                      {markets.map((market) => (
                        <MarketTableRow
                          key={market.code}
                          market={market}
                          onDelete={() => handleDeleteRow(market.code)}
                          onUpdate={() => {
                            marketsMutate();
                            statsMutate();
                          }}
                        />
                      ))}

                      {notFound && <TableNoData notFound={notFound} />}
                    </>
                  )}
                </TableBody>
              </Table>
            </Scrollbar>
          </TableContainer>
        </Card>
      </DashboardContent>

      {/* Create Market Dialog */}
      <MarketCreateEditDialog
        open={createDialog.value}
        onClose={createDialog.onFalse}
        onSuccess={handleCreateSuccess}
      />
    </>
  );
}

// ----------------------------------------------------------------------
// TABLE ROW
// ----------------------------------------------------------------------

type MarketTableRowProps = {
  market: IMarket;
  onDelete: () => void;
  onUpdate: () => void;
};

function MarketTableRow({ market, onDelete, onUpdate }: MarketTableRowProps) {
  const popover = usePopover();
  const confirmDelete = useBoolean();
  const editDialog = useBoolean();

  return (
    <>
      <TableRow hover>
        <TableCell>
          <Label variant="soft" color="primary">
            {market.code}
          </Label>
        </TableCell>

        <TableCell>
          <Typography variant="subtitle2">{market.name}</Typography>
        </TableCell>

        <TableCell>
          <Typography variant="body2" color="text.secondary">
            {market.localName || "-"}
          </Typography>
        </TableCell>

        <TableCell>
          <Label variant="soft" color="default">
            {market.currency}
          </Label>
        </TableCell>

        <TableCell>
          <Typography variant="body2" color="text.secondary">
            {market.defaultLocale}
          </Typography>
        </TableCell>

        <TableCell>
          <Label variant="soft" color={market.isActive ? "success" : "default"}>
            {market.isActive ? "Aktiv" : "Inaktiv"}
          </Label>
        </TableCell>

        <TableCell align="right" sx={{ px: 1 }}>
          <IconButton
            color={popover.open ? "inherit" : "default"}
            onClick={popover.onOpen}
          >
            <Iconify icon="eva:more-vertical-fill" />
          </IconButton>
        </TableCell>
      </TableRow>

      <CustomPopover
        open={popover.open}
        anchorEl={popover.anchorEl}
        onClose={popover.onClose}
        slotProps={{ arrow: { placement: "right-top" } }}
      >
        <MenuItem
          onClick={() => {
            popover.onClose();
            editDialog.onTrue();
          }}
        >
          <Iconify icon="solar:pen-bold" />
          Bearbeiten
        </MenuItem>

        <MenuItem
          onClick={() => {
            popover.onClose();
            confirmDelete.onTrue();
          }}
          sx={{ color: "error.main" }}
        >
          <Iconify icon="solar:trash-bin-trash-bold" />
          Löschen
        </MenuItem>
      </CustomPopover>

      <ConfirmDialog
        open={confirmDelete.value}
        onClose={confirmDelete.onFalse}
        title="Löschen"
        content={`Möchten Sie den Market "${market.name}" (${market.code}) wirklich löschen?`}
        action={
          <Button
            variant="contained"
            color="error"
            onClick={() => {
              onDelete();
              confirmDelete.onFalse();
            }}
          >
            Löschen
          </Button>
        }
      />

      <MarketCreateEditDialog
        open={editDialog.value}
        onClose={editDialog.onFalse}
        market={market}
        onSuccess={() => {
          editDialog.onFalse();
          onUpdate();
        }}
      />
    </>
  );
}

// ----------------------------------------------------------------------
// CREATE/EDIT DIALOG
// ----------------------------------------------------------------------

type MarketCreateEditDialogProps = {
  open: boolean;
  onClose: () => void;
  onSuccess: () => void;
  market?: IMarket;
};

function MarketCreateEditDialog({
  open,
  onClose,
  onSuccess,
  market,
}: MarketCreateEditDialogProps) {
  const isEdit = !!market;

  const [formData, setFormData] = useState({
    code: market?.code || "",
    name: market?.name || "",
    localName: market?.localName || "",
    currency: market?.currency || "EUR",
    defaultLocale: market?.defaultLocale || "",
    isActive: market?.isActive ?? true,
    sortOrder: market?.sortOrder ?? 0,
  });

  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: string, value: string | number | boolean) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
  };

  const handleSubmit = async () => {
    setIsSubmitting(true);
    try {
      if (isEdit) {
        await axios.patch(
          endpoints.system.markets.update(market.code),
          formData,
        );
        toast.success("Markt erfolgreich aktualisiert.");
      } else {
        await axios.post(endpoints.system.markets.create, formData);
        toast.success("Markt erfolgreich erstellt.");
      }
      onSuccess();
    } catch (error: any) {
      const errorMessage =
        error?.response?.data?.error ||
        error?.message ||
        "Fehler beim Speichern.";
      toast.error(errorMessage);
    } finally {
      setIsSubmitting(false);
    }
  };

  // Reset form when dialog opens
  const handleEnter = () => {
    setFormData({
      code: market?.code || "",
      name: market?.name || "",
      localName: market?.localName || "",
      currency: market?.currency || "EUR",
      defaultLocale: market?.defaultLocale || "",
      isActive: market?.isActive ?? true,
      sortOrder: market?.sortOrder ?? 0,
    });
  };

  return (
    <Dialog
      open={open}
      onClose={onClose}
      maxWidth="sm"
      fullWidth
      TransitionProps={{ onEnter: handleEnter }}
    >
      <DialogTitle>
        {isEdit ? "Markt bearbeiten" : "Neuen Markt erstellen"}
      </DialogTitle>

      <DialogContent>
        <Stack spacing={3} sx={{ mt: 1 }}>
          <TextField
            label="Ländercode (ISO 3166-1 alpha-2)"
            placeholder="DE, AT, CH, ..."
            value={formData.code}
            onChange={(e) => handleChange("code", e.target.value.toUpperCase())}
            disabled={isEdit}
            inputProps={{ maxLength: 2 }}
            helperText={
              isEdit
                ? "Code kann nicht geändert werden"
                : "2-stelliger ISO-Ländercode"
            }
            required
          />

          <TextField
            label="Name (Englisch)"
            placeholder="Germany, Austria, ..."
            value={formData.name}
            onChange={(e) => handleChange("name", e.target.value)}
            required
          />

          <TextField
            label="Lokaler Name"
            placeholder="Deutschland, Österreich, ..."
            value={formData.localName}
            onChange={(e) => handleChange("localName", e.target.value)}
          />

          <TextField
            label="Währung (ISO 4217)"
            placeholder="EUR, CHF, ..."
            value={formData.currency}
            onChange={(e) =>
              handleChange("currency", e.target.value.toUpperCase())
            }
            inputProps={{ maxLength: 3 }}
            required
          />

          <TextField
            label="Standard-Locale"
            placeholder="de_DE, de_AT, de_CH, ..."
            value={formData.defaultLocale}
            onChange={(e) => handleChange("defaultLocale", e.target.value)}
            required
          />

          <Stack direction="row" spacing={2}>
            <TextField
              label="Sortierung"
              type="number"
              value={formData.sortOrder}
              onChange={(e) =>
                handleChange("sortOrder", parseInt(e.target.value, 10) || 0)
              }
              sx={{ width: 120 }}
            />

            <TextField
              select
              label="Status"
              value={formData.isActive ? "active" : "inactive"}
              onChange={(e) =>
                handleChange("isActive", e.target.value === "active")
              }
              sx={{ width: 150 }}
            >
              <MenuItem value="active">Aktiv</MenuItem>
              <MenuItem value="inactive">Inaktiv</MenuItem>
            </TextField>
          </Stack>
        </Stack>
      </DialogContent>

      <DialogActions>
        <Button onClick={onClose} color="inherit">
          Abbrechen
        </Button>
        <Button
          onClick={handleSubmit}
          variant="contained"
          disabled={
            isSubmitting ||
            !formData.code ||
            !formData.name ||
            !formData.currency ||
            !formData.defaultLocale
          }
        >
          {isSubmitting ? "Speichern..." : isEdit ? "Speichern" : "Erstellen"}
        </Button>
      </DialogActions>
    </Dialog>
  );
}
