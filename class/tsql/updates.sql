/* 30.08.2017 */
ALTER TABLE [dbo].[Vote_User_Config]
   ADD [Owner_Key] [char](50) NULL;

ALTER TABLE [dbo].[Vote_User_Config]
   ADD [Owner_Login] [nvarchar](60) NULL;