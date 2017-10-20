/* 30.08.2017 */
ALTER TABLE [dbo].[Vote_User_Config]
   ADD [Owner_Key] [char](50) NULL;

ALTER TABLE [dbo].[Vote_User_Config]
   ADD [Owner_Login] [nvarchar](60) NULL;


/* 25.09.2017 - 17.10.2017 */
ALTER TABLE [dbo].[Vote_Object]
   ADD [Tag_Words] [nvarchar](127) NULL;

ALTER TABLE [dbo].[Vote_Object_Rate]
   ADD [Tag] [nvarchar](31) NULL;

ALTER TABLE [dbo].[Vote_User_Config]
   ADD [Question_If_Below] [smallint] NULL DEFAULT 3,
       [Question_If_Above] [smallint] NULL DEFAULT 7,
       [Report_Id] [nvarchar](63) NULL;